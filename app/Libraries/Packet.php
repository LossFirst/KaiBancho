<?php

namespace App\Libraries;

use Cache;
use Log;

class Packet {
    public function create($type, $data = null) {
        $helper = new Helper();
        switch ($type) {
            //string
            case 24:	//show custom, orange notification
            case 64:	//Main channel
            case 66:	//remove channel?
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
    
    public function check($data, $user, $osutoken)
    {
        $output = array();
        if (is_array($data)) {
            $player = new Player();
            $helper = new Helper();
            switch ($data[1]) {
                case 0: //A more indepth packet
                    switch ($data[4]) {
                        default:
                            Log::info($data);
                            Log::info(sprintf("PACKET: %s", implode(array_map("chr", $data))));
                            break;
                    }
                    break;
                case 1: //Chat message
                    $message = new Message();
                    $output = $message->sendToChannel($data, $user);
                    break;
                case 2: //Logout packet (Only gets called if you Alt+F4)
                    Cache::forget($osutoken);
                    break;
                case 3: //Initial fetch for local player OR could be the data for getting all players (Would make even more sense)
                    $bot = (object)array('id' => 2, 'name' => "KaiBanchoo", 'country' => 2);
                    $output = array_merge(
                        $this->create(83, $player->getData($bot)));
                    break;
                case 4: //TODO: Default update
                    if(Cache::tags(['userChat'])->has($user->id)) {
                        $output = array_merge($output, Cache::tags(['userChat'])->get($user->id));
                        Cache::tags(['userChat'])->forget($user->id);
                    }
                    break;
                case 16: //TODO: Spectating [$data[8] = targeted user]
                    break;
                case 25: //Private Message
                    $message = new Message();
                    $output = $message->sendToPlayer($data, $user);
                    break;
                case 64: //Remove channel ($data[10]+ = Channel Name)
                    break;
                case 68: //Join channel
                    if (array_slice($data, -4)[1] == 35) {
                        $output = array_merge(
                            $this->create(64, implode(array_map("chr", array_slice($data, -4))))
                        );
                    }
                    break;
                case 73: //TODO: Add friend [$data[8] = targeted user]
                case 74: //TODO: Remove friend [$data[8] = targeted user]
                    break;
                case 79: //Gets all users online
                    //$output = $player->getOnline();
                    break;
                case 85: //Updates all users, also checks if they are online (I assume)
                    $output = $player->getOnline();
                    $output = array_merge($output, $player->getOnlineDetailed($helper->parsePacket85($data)));
                    break;
                default:
                    Log::info($data);
                    Log::info(sprintf("PACKET: %s", implode(array_map("chr", $data))));
                    break;
            }
        }
        return implode(array_map("chr", $output));
    }
}