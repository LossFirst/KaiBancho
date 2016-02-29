<?php

namespace App\Libraries;

use App\Serializables\bChat;
use App\Serializables\bUserStatus;
use Log;
use App\Libraries\PhpBinaryReader\BinaryReader as BinaryReader;
Use App\Libraries\PhpBinaryReader\Endian as Endian;

class Packet {
    public function create($type, $data = null) {
        $helper = new Helper();
        switch ($type) {
            case Packets::OUT_OrangeNotification:
            case Packets::OUT_ChannelJoined:
            case Packets::OUT_ChannelDeny:
            case Packets::OUT_SwitchTournyServer:
            case Packets::OUT_Announcement:
            case Packets::OUT_BlackScreenNotification:
                $toreturn = $helper->ULeb128($data);
                break;
            case Packets::OUT_Popup:
            case Packets::OUT_RoomHostTransferred:
            case Packets::OUT_Monitor:
                $toreturn = array();
                break;
            case Packets::OUT_PlayerLocaleInfo:
                $toreturn = array_merge(
                    unpack('C*', pack('L*', $data['id'])),
                    $helper->ULeb128($data['playerName']),
                    unpack('C*', pack('C*', $data['utcOffset'])),
                    unpack('C*', pack('C*', $data['country'])),
                    unpack('C*', pack('C*', $data['playerRank'])),
                    unpack('C*', pack('f*', $data['longitude'])),
                    unpack('C*', pack('f*', $data['latitude'])),
                    unpack('C*', pack('L*', $data['globalRank']))
                );
                break;
            case Packets::OUT_HandleStatsUpdate:
                $toreturn = array_merge(
                    unpack('C*', pack('L*', $data['id'])),
                    unpack('C*', pack('C*', $data['bStatus'])),
                    $helper->ULeb128($data['string0']),
                    $helper->ULeb128($data['string1']),
                    unpack('C*', pack('L*', $data['mods'])),
                    unpack('C*', pack('C*', $data['playmode'])),
                    unpack('C*', pack('L*', $data['int0'])),
                    $helper->GetLongBytes($data['score']),
                    unpack('C*', pack('f*', $data['accuracy'])),
                    unpack('C*', pack('L*', $data['playcount'])),
                    $helper->GetLongBytes($data['experience']),
                    unpack('C*', pack('L*', $data['int1'])),
                    unpack('C*', pack('S*', $data['pp']))
                );
                break;
            case Packets::OUT_ChannelList:
                $toreturn = array_merge(
                    $helper->ULeb128($data[0]),
                    $helper->ULeb128($data[1]),
                    unpack('C*', pack('S*', $data[2]))
                );
                break;
            case Packets::OUT_SendChatMSG:
                $toreturn = array_merge(
                    $helper->ULeb128($data[0]),
                    $helper->ULeb128($data[1]),
                    $helper->ULeb128($data[2]),
                    unpack('C*', pack('I', $data[3]))
                );
                break;
            case Packets::OUT_UserFriends:
            case Packets::OUT_OnlineList:
                $l1 = unpack('C*', pack('S', sizeof($data)));
                $toreturn = array();
                foreach ($data as $key => $value) {
                    $toreturn = array_merge($toreturn, unpack('C*', pack('I', $value)) );
                }
                $toreturn = array_merge($l1, $toreturn);
                break;
            case Packets::OUT_SpectatorFrames:
                $toreturn = $data;
                break;
            case Packets::OUT_LoginRequest:
            case Packets::OUT_HandleUserDisconnect:
            case Packets::OUT_UserGroup:
            case Packets::OUT_Protocol:
            case Packets::OUT_BanStatus:
            case Packets::OUT_SpectatorJoin:
            default:
                $toreturn = unpack('C*', pack('L*', $data));
                break;
        }

        return array_merge(
            unpack('S*', pack("L*", $type)),			//type
            array(0),									//unused byte
            unpack('C*', pack('L', sizeof($toreturn))),	//length
            $toreturn									//data
        );
    }
    
    public function check($body, $userID, $osutoken)
    {
        $output = array();
        $data = unpack('C*', $body); //For now for backwards compat till I convert the whole packet reading.
        $br = new BinaryReader($body, Endian::ENDIAN_LITTLE);
        $packetID = $br->readUInt16();
        $br->readUBits(8); //Skip 1 byte
        $packetLength = $br->readUInt32();
        if (is_array($data)) {
            $player = new Player();
            $helper = new Helper();
            $redisPacket = new RedisPacket();
            switch ($packetID) {
                case Packets::IN_SetUserState:
                    $stuff = array();
                    $format = 'CPacket/x2/CLength/x3/CStatus/x/CSongLength';
                    $stuff = array_merge($stuff, unpack($format, $body));
                    $format = sprintf('@10/A%dSongName/x/CSongChecksumLength', $stuff['SongLength']);
                    $stuff = array_merge($stuff, unpack($format, $body));
                    $format = sprintf('@%d/x2/A%dSongChecksum', 10+(integer)$stuff['SongLength'], $stuff['SongChecksumLength']);
                    $stuff = array_merge($stuff, unpack($format, $body));
                    $format = sprintf('@%d/x2/CMode/CThingOne/CThingTwo', $stuff['Length']);
                    $stuff = array_merge($stuff, unpack($format, $body));
                    $player->setStatus($userID, $stuff);
                    break;
                case Packets::IN_ReceivePM:
                case Packets::IN_RecieveChatMSG: //Chat message
                    $messageData = array();
                    $format = 'CPacket/x2/CLength/x6/CMessageLength';
                    $messageData = array_merge($messageData, unpack($format, $body));
                    if($messageData['MessageLength'] > 127) {
                        $output = $this->create(Packets::OUT_OrangeNotification, "Message is too long, please shorten it");
                    } else {
                        $format = sprintf('@12/X/A%dMessage/x/CChannelLength',$messageData['MessageLength']);
                        $messageData = array_merge($messageData, unpack($format, $body));
                        $format = sprintf('@%d/A%dChannel', 13+$messageData['MessageLength'], $messageData['ChannelLength']);
                        $messageData = array_merge($messageData, unpack($format, $body));
                        $message = new RedisMessage();
                        $message->SendMessage($player->getDatafromID($userID), $messageData);
                    }
                    break;
                case Packets::IN_Logout:
                    $player->expireToken($osutoken);
                    break;
                case Packets::IN_LocalUpdate:
                    $output = $this->create(Packets::OUT_HandleStatsUpdate ,$player->getDataDetailed($player->getDatafromID($userID)));
                    break;
                case Packets::IN_KeepAlive:
                    $output = $player->getOnlineDetailed($player->getFriends($userID));
                    break;
                case Packets::IN_StartSpectating:
                    break;
                case Packets::IN_StopSpectating:
                    break;
                case Packets::IN_SpectatingData:
                    break;
                case Packets::IN_MPLeave:
                case Packets::IN_MPJoin:
                case Packets::IN_RoomCreate:
                case Packets::IN_RoomJoin:
                case Packets::IN_RoomLeave:
                    break;
                case Packets::IN_JoinChannel: //Join Channel
                    $ChannelData = array();
                    $format = 'CPacket/x2/CHeaderLength/x4/CChannelLength';
                    $ChannelData = array_merge($ChannelData, unpack($format, $body));
                    $format = sprintf('@9/A%dChannel',$ChannelData['ChannelLength']);
                    $ChannelData = array_merge($ChannelData, unpack($format, $body));
                    if($ChannelData['ChannelLength'] > 1 && $ChannelData['ChannelLength'] < 32 && strchr($ChannelData['Channel'], " ") === false)
                    {
                        $output = $this->create(Packets::OUT_ChannelJoined, $ChannelData['Channel']);
                    } else {
                        $output = $this->create(Packets::OUT_ChannelDeny, $ChannelData['Channel']);
                    }
                    break;
                case Packets::IN_BeatmapInformation: //Some thing to do with checking beatmaps at start? (Yea, we won't touch this, looks like it'll be too much (up to 4000+ lines))
                    break;
                case Packets::IN_AddFriend:
                    $friend = unpack('x7/C', $body);
                    $player->addFriend($userID, $friend[1]);
                    break;
                case Packets::IN_RemoveFriend:
                    $exfriend = unpack('x7/C', $body);
                    $player->removeFriend($userID, $exfriend[1]);
                    break;
                case Packets::IN_LeaveChannel:
                    $ChannelData = array();
                    $format = 'CPacket/x2/CHeaderLength/x4/CChannelLength';
                    $ChannelData = array_merge($ChannelData, unpack($format, $body));
                    $format = sprintf('@9/A%dChannel',$ChannelData['ChannelLength']);
                    $ChannelData = array_merge($ChannelData, unpack($format, $body));
                    $output = $this->create(Packets::OUT_ChannelDeny, $ChannelData['Channel']);
                    break;
                case Packets::IN_OnlinePlayers:
                    $output = $player->getOnline();
                    break;
                case Packets::IN_OnlineStats:
                    $output = $player->getOnlineDetailed($helper->parsePacket85($data));
                    break;
                case Packets::IN_RoomInvite:
                    break;
                default:
                    Log::info($data);
                    Log::info(sprintf("PACKET: %s", implode(array_map("chr", $data))));
                    break;
            }
            $message = new RedisMessage();
            $output = array_merge($output, $message->GetMessage($userID), $player->getOnline());
        }
        return implode(array_map("chr", $output));
    }

    public function debug($data)
    {
        $br = new BinaryReader($data);
        $packetEnd = false;
        while(!$packetEnd) {
            if($br->getPosition() != 0)
            {
                if($br->getPosition() - $br->getEofPosition() <= 2)
                {
                    break;
                }
                $br->readUBits(8);
            }
            $packetID = $br->readUInt16();
            $br->readUBits(8);
            $packetLength = $br->readUInt32();
            $position = $br->getPosition();
            $EOF = $br->getEofPosition();
            if($packetLength > $EOF - $position)
            {
                Log::info("Invalid Packet!");
                $packetEnd = true;
                break;
            }
            switch($packetID)
            {
                case Packets::IN_SetUserState:
                    $status = new bUserStatus();
                    $status->bUserStatus($br);
                    break;
                case Packets::IN_KeepAlive:
                    break;
                case Packets::IN_ReceivePM:
                case Packets::IN_RecieveChatMSG:
                    $chat = new bChat();
                    $chat->bChat($br);
                    break;
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
