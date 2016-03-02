<?php

namespace App\Libraries;

use App\Libraries\PhpBinaryReader\BinaryWriter;
use App\Libraries\PhpBinaryReader\BinaryReader;
use App\Serializables\bChat;
use App\Serializables\bUserList;
use App\Serializables\bUserStatus;
use Log;

class newPacket
{
    private $output = array();

    public function create($type, $data = null) {
        $stream = new BinaryWriter();
        $endStream = new BinaryWriter();
        switch ($type) {
            case Packets::OUT_SendChatMSG:
                $stream->writeULEB128($data[0]);
                $stream->writeULEB128($data[1]);
                $stream->writeULEB128($data[2]);
                $stream->writeUInt32($data[3]);
                break;
            default:
                break;
        }
        $endStream->writeUInt16($type);
        $endStream->writeBytes(0);
        $endStream->writeUInt32(sizeof($stream->inputHandle));
        $this->output = array_merge($this->output, array_merge($endStream->inputHandle, $stream->inputHandle));
    }

    public function read($data, $userID)
    {
        $stream = new BinaryReader($data);
        $user = with(new Player())->getDatafromID($userID);
        $packetEnd = false;
        while(!$packetEnd) {
            $bUserStatus = new bUserStatus($stream);
            $bUserList = new bUserList($stream);
            $bChat = new bChat($stream);
            $redisPacket = new RedisPacket();
            if($stream->getPosition() != 0)
            {
                if($stream->getPosition() - $stream->getEofPosition() <= 2)
                {
                    break;
                }
                $stream->readBytes(1);
            }
            $packetID = $stream->readUInt16();
            $stream->readBytes(1);
            $packetLength = $stream->readUInt32();
            if($packetLength > 4096 && !($stream->canReadBytes($packetLength))) break;
            switch($packetID)
            {
                case Packets::IN_SetUserState:
                    $bUserStatus->readUserStatus();
                    break;
                case Packets::IN_KeepAlive:
                    break;
                case Packets::IN_ReceivePM:
                case Packets::IN_RecieveChatMSG:
                    $bChat->readMessage();
                    $this->create(Packets::OUT_SendChatMSG, array($user->name, $bChat->message, $bChat->reciever, $userID));
                    break;
                case Packets::IN_OnlineStats:
                    $bUserList->getOnlineStats();
                    break;
                case Packets::IN_BeatmapInformation:
                    $packetEnd = true;
                    break;
                case Packets::IN_Logout:
                case Packets::IN_LocalUpdate:
                case Packets::IN_StartSpectating:
                case Packets::IN_StopSpectating:
                case Packets::IN_SpectatingData:
                case Packets::IN_MPLeave:
                case Packets::IN_MPJoin:
                case Packets::IN_RoomCreate:
                case Packets::IN_RoomJoin:
                case Packets::IN_RoomLeave:
                    $packetEnd = true;
                    break;
                case Packets::IN_LeaveChannel:
                    $bChat->readChannel();
                    $bChat->leaveChannel($userID);
                    break;
                case Packets::IN_JoinChannel:
                    $bChat->readChannel();
                    $bChat->joinChannel($userID);
                    break;
                case Packets::IN_AddFriend:
                case Packets::IN_RemoveFriend:
                case Packets::IN_OnlinePlayers:
                case Packets::IN_RoomInvite:
                    $packetEnd = true;
                    break;
                default:
                    log::info(sprintf("Packet: %d || Length: %d", $packetID, $packetLength));
                    $packetEnd = true;
                    break;
            }
        }
    }
}