<?php

namespace App\Libraries;

use App\Libraries\PhpBinaryReader\BinaryWriter;
use App\Libraries\PhpBinaryReader\BinaryReader;
use App\Serializables\bChat;
use App\Serializables\bUserList;
use App\Serializables\bUserStatus;
use Log;

/**
 * Class PacketHandler
 * @package App\Libraries
 */
class PacketHandler
{
    /**
     * @var array
     */
    public $output = array();

    /**
     * @param $type
     * @param null $data
     */
    public function create($type, $data = null) {
        $stream = new BinaryWriter();
        $endStream = new BinaryWriter();
        switch ($type) {
            case Packets::OUT_SendChatMSG:
                $stream->writeULEB128($data['UserName']);
                $stream->writeULEB128($data['Message']);
                $stream->writeULEB128($data['Receiver']);
                $stream->writeUInt32($data['UserID']);
                break;
            case Packets::OUT_PlayerLocaleInfo:
                $stream->writeUInt32($data['UserID']);
                $stream->writeBytes($data['Status']);
                $stream->writeULEB128($data['Beatmap']);
                $stream->writeULEB128($data['BeatmapHash']);
                $stream->writeUInt32($data['Mods']);
                $stream->writeBytes($data['PlayMode']);
                $stream->writeUInt64($data['Score']);
                $stream->writeFloat($data['Accuracy']);
                $stream->writeUInt64($data['Experience']);
                $stream->writeUInt32($data['GlobalRank']);
                $stream->writeUInt16($data['PP']);
                break;
            default:
                break;
        }
        $endStream->writeUInt16($type);
        $endStream->writeBytes(0);
        $endStream->writeUInt32(sizeof($stream->inputHandle));
        $this->output = array_merge($this->output, array_merge($endStream->inputHandle, $stream->inputHandle));
    }

    /**
     * @param $data
     * @param $userID
     */
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
                    $bChat->receiveMessage($userID);
                    break;
                case Packets::IN_ReceivePM:
                case Packets::IN_RecieveChatMSG:
                    $bChat->readMessage();
                    $bChat->sendMessage($userID);
                    break;
                case Packets::IN_OnlineStats:
                    $bUserList->getOnlineStats();
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
                case Packets::IN_BeatmapInformation:
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