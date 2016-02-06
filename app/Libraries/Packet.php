<?php

namespace App\Libraries;

use Cache;
use Log;

use App\Libraries\Helper as Helper;

class Packet {
    public function create($type, $data = null) {
        $helper = new Helper();
        $toreturn = '';
        $length = 0;
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
                $toreturn = array();
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
                    $message = array();
                    foreach (array_slice($data, 11) as $item) {
                        if ($item == 11) {
                            break;
                        }
                        array_push($message, $item);
                    }
                    $channel = array();
                    foreach (array_slice($data, 11 + count($message) + 2) as $item) {
                        if ($item == 0) {
                            break;
                        }
                        array_push($channel, $item);
                    }
                    if (Cache::has('currentLogin')) {
                        $currentLogins = Cache::get("currentLogin");
                        foreach ($currentLogins as $token) {
                            if ($token != $osutoken) {
                                if (Cache::tags(['userChat'])->has($token)) {
                                    $previousMessages = Cache::tags(['userChat'])->get($token);
                                    Cache::tags(['userChat'])->put($token, array_merge($previousMessages,
                                        $this->create(07, array($user->name, implode(array_map("chr", $message)), implode(array_map("chr", $channel)), $user->id))
                                    ), 1);
                                } else {
                                    Cache::tags(['userChat'])->put($token, array_merge($this->create(07, array($user->name, implode(array_map("chr", $message)), implode(array_map("chr", $channel)), $user->id))), 1);
                                }
                            }
                        }
                    }
                    break;
                case 2: //Logout packet
                    Log::info("Logout packet was called?"); //Only gets called when you Alt+F4 (weird)
                    Cache::forget($osutoken);
                    break;
                case 3: //Initial fetch for local player OR could be the data for getting all players (Would make even more sense)
                    $output = array_merge(
                        $this->create(83, array(	//bancho bob
                            'id' => 2,
                            'playerName' => 'KaiBanchoo',
                            'utcOffset' => 0 + 24,
                            'country' => 1,
                            'playerRank' => 0,
                            'longitude' => 0,
                            'latitude' => 0,
                            'globalRank' => 0,
                        )),
                        $this->create(96, array_merge($player->getAll(), array($user->id))));
                    break;
                case 4: //Default update (Need to work on this)
                    if (Cache::has('currentLogin')) {
                        $output = $player->getAllDetailed();
                    }
                    if (Cache::tags(['userChat'])->has($osutoken)) {
                        $output = array_merge($output, Cache::tags(['userChat'])->get($osutoken));
                        Cache::tags(['userChat'])->forget($osutoken);
                    }
                    break;
                case 16: //Spectating [$data[8] = targeted user]
                    break;
                case 25: //Private Message
                    if (Cache::has('currentLogin')) {
                        $message = array();
                        foreach (array_slice($data, 11) as $item) {
                            if ($item == 11) {
                                break;
                            }
                            array_push($message, $item);
                        }
                        $toPerson = array();
                        foreach (array_slice($data, 11 + count($message) + 2) as $item) {
                            if ($item == 0) {
                                break;
                            }
                            array_push($toPerson, $item);
                        }
                        $currentLogins = Cache::get("currentLogin");
                        foreach ($currentLogins as $token) {
                            if (Cache::tags(['user'])->has($token)) {
                                $person = Cache::tags(['user'])->get($token);
                                if ($person->name == implode(array_map("chr", $toPerson))) {
                                    if (Cache::tags(['userChat'])->has($token)) {
                                        $previousMessages = Cache::tags(['userChat'])->get($token);
                                        Cache::tags(['userChat'])->put($token, array_merge($previousMessages,
                                            $this->create(07, array($user->name, implode(array_map("chr", $message)), implode(array_map("chr", $toPerson)), $user->id))
                                        ), 1);
                                    } else {
                                        Cache::tags(['userChat'])->put($token, array_merge($this->create(07, array($user->name, implode(array_map("chr", $message)), implode(array_map("chr", $toPerson)), $user->id))), 1);
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    break;
                case 68: //Join channel?
                    if (array_slice($data, -4)[1] == 35) {
                        $output = array_merge(
                            $this->create(64, implode(array_map("chr", array_slice($data, -4))))
                        );
                    }
                    break;
                case 73: //Add friend [$data[8] = targeted user]
                case 74: //Remove friend [$data[8] = targeted user]
                case 79: //Not sure, might be getting more information on targeted user [$data[8] = targeted user]
                case 85: //Not sure, but sends a packet every second if PM box is opened [$data[8] = targeted user]
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