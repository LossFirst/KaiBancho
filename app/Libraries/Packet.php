<?php

namespace App\Libraries;

use Log;

class Packet {
    public function create($type, $data = null) {
        $helper = new Helper();
        switch ($type) {
            //string
            case 24:	//show custom, orange notification
            case 64:	//Join Channel
            case 66:	//Remove Channel
            case 105:	//show scary msg
                $toreturn = $helper->ULeb128($data);
                break;
            //empty
            case 23:
            case 50:	//something with match-confirm
            case 59:	//something with chat channels?
            case 80:	//Sneaky Shizzle
                $toreturn = array();
                break;
            //Class17 (player data 02)
            case 83:	//local player
                $toreturn = array_merge(
                    unpack('C*', pack('L*', $data['id'])),
                    $helper->ULeb128($data['playerName']),				//TODO: fix names
                    unpack('C*', pack('C*', $data['utcOffset'])),
                    unpack('C*', pack('C*', $data['country'])),
                    unpack('C*', pack('C*', $data['playerRank'])),
                    unpack('C*', pack('f*', $data['longitude'])),
                    unpack('C*', pack('f*', $data['latitude'])),
                    unpack('C*', pack('L*', $data['globalRank']))
                );
                break;
            //Class19 (player data 01)
            case 11:	//some player thing
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
            //Class20 (string, string, short)
            case 65: 	//chat channel with title
                $toreturn = array_merge(
                    $helper->ULeb128($data[0]),
                    $helper->ULeb128($data[1]),
                    unpack('C*', pack('S*', $data[2]))
                );
                break;
            //chat Message
            case 07:
                $toreturn = array_merge(
                    $helper->ULeb128($data[0]),
                    $helper->ULeb128($data[1]),
                    $helper->ULeb128($data[2]),
                    unpack('C*', pack('I', $data[3]))
                );
                break;
            //int[] (short length, int[length])
            case 72:	//friend list, int[]
            case 96:	//list of online players
                $l1 = unpack('C*', pack('S', sizeof($data)));
                $toreturn = array();
                foreach ($data as $key => $value) {
                    $toreturn = array_merge($toreturn, unpack('C*', pack('I', $value)) );
                }
                $toreturn = array_merge($l1, $toreturn);
                break;
            //int32
            case 5:		//user id
            case 12:    //despawn user panel
            case 18:   //spectator replay data?
            case 71:	//user rank
            case 75: 	//cho protocol
            case 92:	//ban status
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
        if (is_array($data)) {
            $player = new Player();
            $helper = new Helper();
            $packetNum = unpack('C', $body);
            switch ($packetNum[1]) {
                case 0: //Update local player
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
                    $output = $this->create(11 ,$player->getDataDetailed($player->getDatafromID($userID)));
                    break;
                case 1: //Chat message
                    $messageData = array();
                    $format = 'CPacket/x2/CLength/x6/CMessageLength';
                    $headerData = unpack($format, $body);
                    $messageData = array_merge($messageData, $headerData);
                    $format = sprintf('@12/X/A%dMessage/x/CChannelLength/A*Channel', $headerData['MessageLength']);
                    $bodyData = unpack($format, $body);
                    $messageData = array_merge($messageData, $bodyData);

                    $message = new RedisMessage();
                    $message->SendMessage($player->getDatafromID($userID), $messageData);
                    break;
                case 2: //Logout packet
                    $player->expireToken($osutoken);
                    break;
                case 3: //Initial fetch for local player OR could be the data for getting all players (Would make even more sense)
                    break;
                case 4: //TODO: Default chat update
                    $message = new RedisMessage();
                    $output = $message->GetMessage($userID);
                    break;
                case 16: //TODO: Spectating (Start spectating)
                    break;
                case 17: //TODO: Spectating (Stop spectating)
                    break;
                case 18: //TODO: Spectating (Spectator frames sent when a user trys to spectate or when someone starts playing a beatmap?)
                    break;
                case 25: //Private Message
                    $messageData = array();
                    $format = 'CPacket/x2/CLength/x6/CMessageLength';
                    $messageData = array_merge($messageData, unpack($format, $body));
                    $format = sprintf('@12/X/A%dMessage/x/CChannelLength/A*Channel', $messageData['MessageLength']);
                    $messageData = array_merge($messageData, unpack($format, $body));

                    $message = new RedisMessage();
                    $message->SendMessage($player->getDatafromID($userID), $messageData);
                    break;
                case 29: //TODO: Multiplayer (List of lobbies)
                    break;
                case 31: //TODO: Multiplayer (Create Lobby)
                    break;
                case 32: //TODO: Multiplayer (Join Lobby)
                    break;
                case 33: //TODO: Multiplayer (Leave Lobby)
                    break;
                case 63: //Join Channel
                    $ChannelData = array();
                    $format = 'CPacket/x2/CHeaderLength/x4/CChannelLength';
                    $ChannelData = array_merge($ChannelData, unpack($format, $body));
                    $format = '@9/'.sprintf('A%dChannel',$ChannelData['ChannelLength']);
                    $ChannelData = array_merge($ChannelData, unpack($format, $body));

                    $output = $this->create(64, $ChannelData['Channel']);
                    break;
                case 68: //Some thing to do with checking beatmaps at start? (Yea, we won't touch this, looks like it'll be too much (up to 4000+ lines))
                    break;
                case 73: //TODO: Add friend
                case 74: //TODO: Remove friend
                    break;
                case 78: //Remove channel
                    $ChannelData = array();
                    $format = 'CPacket/x2/CHeaderLength/x4/CChannelLength';
                    $ChannelData = array_merge($ChannelData, unpack($format, $body));
                    $format = sprintf('@9/A%dChannel',$ChannelData['ChannelLength']);
                    $ChannelData = array_merge($ChannelData, unpack($format, $body));

                    $output = $this->create(66, $ChannelData['Channel']);
                    break;
                case 79: //Gets all users online
                    $output = $player->getOnline();
                    break;
                case 85: //Updates all users, also checks if they are online (I assume)
                    $output = $player->getOnline();
                    $output = array_merge($output, $player->getOnlineDetailed($helper->parsePacket85($data)));
                    break;
                case 87: //TODO: Multiplayer (Invite player to lobby)
                    break;
                default:
                    Log::info($data);
                    Log::info(sprintf("PACKET: %s", implode(array_map("chr", $data))));
                    break;
            }
        }
        return implode(array_map("chr", $output));
    }

    public function debug($data)
    {
        $packet = unpack('C1', $data);
        if($packet[1] == 78) //reduce results
        {
            //More Debugging soon
        }
    }
}
