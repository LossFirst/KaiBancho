<?php

namespace App\Libraries;

use Cache;
use App\User;
use Log;

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
        Log::info($output);
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
            $id = $this->getIDfromToken($token);
            if($id != -1) {
                array_push($output, $this->getIDfromToken($token));
            }
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
            $output = array_merge($output, $packet->create(83, $this->getData($user)));
        }
        return $output;
    }

    public function getDatafromID($id)
    {
        if($id != -1) {
            $user = User::find($id);
            return $user;
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
            'score' => $player->OsuUserStats->total_score,			//long 	score
            'accuracy' => $this->getAccuracy($player),	//float accuracy
            'playcount' => $player->OsuUserStats->playcount,			//int playcount
            'experience' => 0,			//long 	experience
            'int1' => 0,	//int 	global rank?
            'pp' => 0,			//short	pp 				if set, will use?
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
        if(Cache::has('currentLogin')) {
            $current = Cache::get('currentLogin');
            array_push($current, $token);
            Cache::put('currentLogin', $current, 999);
        }
    }

    public function updateToken($token, $userID)
    {
        Cache::tags(['userToken'])->put($token, $userID, 1);
    }

    public function getAccuracy($player)
    {
        $totalHits = ($player->OsuUserStats->count50 + $player->OsuUserStats->count100 + $player->OsuUserStats->count300 + $player->OsuUserStats->countmiss) * 300;
        $hits = $player->OsuUserStats->count50 * 50 + $player->OsuUserStats->count100 * 100 + $player->OsuUserStats->count300 * 300;
        if($hits && $totalHits != 0) {
            return $hits / $totalHits;
        } else {
            return 0;
        }
    }
}