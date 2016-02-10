<?php

namespace App\Libraries;

use App\User;
use Log;
use DB;
use Redis;

class Player {
    public function getAllTokens()
    {
        $redis = Redis::connection();
        $allKeys = $redis->keys('CurrentlyLoggedIn:*');
        Log::info($allKeys);
        return $allKeys;
    }

    public function getIDfromToken($token, $format = false)
    {
        if($format)
        {
            $token = sprintf("CurrentlyLoggedIn:%s", $token);
        }
        $redis = Redis::connection();

        return $redis->get($token);
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
            $output = array_merge($output, $packet->create(83, $this->getData($user)));
            $output = array_merge($output, $packet->create(11, $this->getDataDetailed($user)));
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
            'globalRank' => $this->getUserRank($player),
        );
    }

    public function getUserRank($player)
    {
        if($player->OsuUserStats->ranked_score > 0) {
            $rank = DB::table('osu_user_stats')
                ->select(DB::raw('FIND_IN_SET( ranked_score, (SELECT GROUP_CONCAT( ranked_score ORDER BY ranked_score DESC ) FROM osu_user_stats )) AS rank'))
                ->where('user_id', '=', $player->id)
                ->orderBy('rank', 'asc')
                ->first();
            return $rank->rank;
        }
        return 0;
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
            'score' => $player->OsuUserStats->ranked_score,			//long 	score
            'accuracy' => $this->getAccuracy($player),	//float accuracy
            'playcount' => $player->OsuUserStats->playcount,			//int playcount
            'experience' => 0,			//long 	experience
            'int1' => $this->getUserRank($player),	//int 	global rank?
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
        $redis = Redis::connection();
        $redis->set(sprintf("CurrentlyLoggedIn:%s", $token), $user->id);
        $redis->expire(sprintf("CurrentlyLoggedIn:%s", $token), 30);
    }

    public function updateToken($token)
    {
        $redis = Redis::connection();
        $redis->expire(sprintf("CurrentlyLoggedIn:%s", $token), 30);
    }

    public function expireToken($token)
    {
        $redis = Redis::connection();
        $redis->del(sprintf("CurrentlyLoggedIn:%s", $token));
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