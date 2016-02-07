<?php

namespace App\Libraries;

use Cache;
use App\User;

class Player {
    public function getAllTokens()
    {
        $output = array();
        if(Cache::has('currentLogin')) {
            $currentUsers = Cache::get('currentLogin');
            foreach($currentUsers as $token)
            {
                if(cache::tags(['userToken'])->has($token))
                {
                    array_push($output, $token);
                }
            }
        }
        return $output;
    }

    public function getIDfromToken($token)
    {
        if(Cache::tags(['userToken'])->has($token))
        {
            return Cache::tags(['userToken'])->get($token);
        }
        return -1;
    }

    public function getAllIDs($tokens)
    {
        $output = array();
        foreach($tokens as $token)
        {
            array_push($output, $this->getIDfromToken($token));
        }
        return $output;
    }

    public function getOnline()
    {
        $packet = new Packet();
        $userIDArray = array();
        $ids = $this->getAllIDs($this->getAllTokens());
        foreach($ids as $id) {
            array_push($userIDArray, $id);
        }
        $output = $packet->create(96, $userIDArray);
        return $output;
    }

    public function getOnlineDetailed($ids)
    {
        $packet = new Packet();
        $output = array();
        foreach($ids as $id) {
            $user = $this->getDatafromID($id);
            $output = array_merge($output, $packet->create(83,$this->getData($user)));
        }
        return $output;
    }

    public function getDatafromID($id)
    {
        if($id != -1) {
            if (Cache::tags(['userData'])->has($id)) {
                return Cache::tags(['userData'])->get($id);
            } else {
                $user = User::findOrFail($id);
                Cache::tags(['userData'])->put($id, $user);
                return $user;
            }
        }
        return false;
    }

    public function getDatafromToken($token = null)
    {
        if(!is_null($token)) {
            $user = $this->getDatafromID($this->getIDfromToken($token));
            return $user;
        }
        return false;
    }

    public function getData($player)
    {
        return array(
            'id' => $player->id,
            'playerName' => $player->name,
            'utcOffset' => 0 + 24,
            'country' => $player->country,
            'playerRank' => 0,
            'longitude' => 0,
            'latitude' => 0,
            'globalRank' => 0,
        );
    }

    public function getDataDetailed($player)
    {
        return array(		//more local player data
            'id' => $player->id,
            'bStatus' => 0,		//byte
            'string0' => '',	//String
            'string1' => '',	//string
            'mods' => 0,		//int
            'playmode' => 0,	//byte
            'int0' => 0,		//int
            'score' => $player->total_score,			//long 	score
            'accuracy' => $player->accuracy,	//float accuracy
            'playcount' => 0,			//int playcount
            'experience' => 0,			//long 	experience
            'int1' => 0,	//int 	global rank?
            'pp' => $player->pp_raw,			//short	pp 				if set, will use?
        );
    }

    public function isPlayerOnline($name)
    {
        $ids = $this->getAllIDs($this->getAllTokens());
        foreach($ids as $id) {
            $user = $this->getDatafromID($id);
            if($user != false) {
                if ($name == $user->name) {
                    return $user;
                }
            }
        }
        return false;
    }

    public function setToken($token, $user)
    {
        Cache::tags(['userToken'])->put($token, $user->id, 1);
        Cache::tags(['userData'])->put($user->id, $user, 60);
        if(Cache::has('currentLogin'))
        {
            $current = Cache::get('currentLogin');
            array_push($current, $token);
            Cache::put('currentLogin', $current, 60);
        }
    }

    public function updateToken($token, $user)
    {
        Cache::tags(['userToken'])->put($token, $user->id, 1);
        Cache::tags(['userData'])->put($user->id, $user, 60);
    }
}