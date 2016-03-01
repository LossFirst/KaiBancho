<?php

namespace App\Libraries;

use App\User;
use App\UserFriends;
use Log;
use DB;
use Redis;

class Player {
    public function getAllTokens()
    {
        $redis = Redis::connection();
        $allKeys = $redis->keys('CurrentlyLoggedIn:*');
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
            if($this->isIDOnline($id)) {
                array_push($userIDArray, $id);
            }
        }
        $output = $packet->create(Packets::OUT_OnlineList, $userIDArray);
        return $output;
    }

    public function getOnlineDetailed($ids)
    {
        $packet = new Packet();
        $output = array();
        foreach($ids as $id) {
            if($this->isIDOnline($id)) {
                $user = $this->getDatafromID($id);
                $output = array_merge($output, $packet->create(Packets::OUT_PlayerLocaleInfo, $this->getData($user)));
                $output = array_merge($output, $packet->create(Packets::OUT_HandleStatsUpdate, $this->getDataDetailed($user)));
            } else {
                $output = array_merge($output, $packet->create(Packets::OUT_HandleUserDisconnect, $id));
            }
        }
        return $output;
    }

    public function getFriends($id)
    {
        $friends = UserFriends::where('user_id', $id)->get();
        $friendsList = array();
        foreach($friends as $friend)
        {
            array_push($friendsList, $friend->friended_id);
        }
        return $friendsList;
    }

    public function getStatus($id)
    {
        $redis = Redis::connection();
        $data = $redis->get(sprintf("UserStatus:%d",$id));
        return json_decode($data, true);
    }

    public function addFriend($userid, $friendid)
    {
        UserFriends::create(['user_id' => $userid, 'friended_id' => $friendid])->save();
    }

    public function removeFriend($userid, $exfriendid)
    {
        $friend = UserFriends::where('user_id', $userid)->where('friended_id', $exfriendid)->first();
        $friend->delete();
    }

    public function setStatus($id, $data)
    {
        $redis = Redis::connection();
        $redis->set(sprintf("UserStatus:%d", $id), json_encode($data));
        $redis->expire(sprintf("UserStatus:%d", $id), (60 * 5));
    }

    public function isIDOnline($id)
    {
        $redis = Redis::connection();
        if($redis->exists(sprintf("CurrentlyLoggedInID:%d",$id)))
        {
            return true;
        };
        return false;
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
            'playerRank' => $player->usergroup,
            'longitude' => 0,
            'latitude' => 0,
            'globalRank' => $this->getUserRank($player),
        );
    }

    public function getUserRank($player, $mode = 0)
    {
        switch($mode)
        {
            case 0:
                $data = $player->OsuUserStats;
                $table = 'osu_user_stats';
                break;
            case 1:
                $data = $player->TaikoUserStats;
                $table = 'taiko_user_stats';
                break;
            case 2:
                $data = $player->CTBUserStats;
                $table = 'ctb_user_stats';
                break;
            case 3:
                $data = $player->ManiaUserStats;
                $table = 'mania_user_stats';
                break;
        }
        if($data->pp > 0) {
            $rank = DB::table('osu_user_stats')
                ->select(DB::raw(sprintf('FIND_IN_SET( pp, (SELECT GROUP_CONCAT( pp ORDER BY pp DESC ) FROM %s )) AS rank', $table)))
                ->where('user_id', '=', $player->id)
                ->orderBy('rank', 'asc')
                ->first();
            return $rank->rank;
        }
        return 0;
    }

    public function getDataDetailed($player)
    {
        $status = $this->getStatus($player->id);
        if(empty($status))
        {
            $status = array(
            'SongName' => '',
            'SongChecksum' => '',
            'Mode' => 0,
            'Status' => 1);
        }
        switch($status['Mode'])
        {
            case 0:
                $data = $player->OsuUserStats;
                break;
            case 1:
                $data = $player->TaikoUserStats;
                break;
            case 2:
                $data = $player->CTBUserStats;
                break;
            case 3:
                $data = $player->ManiaUserStats;
                break;
        }
        return array(		//more local player data
            'id' => $player->id,
            'bStatus' => $status['Status'],		//byte
            'string0' => $status['SongName'],	//String
            'string1' => $status['SongChecksum'],	//string
            'mods' => 0,		//int
            'playmode' => $status['Mode'],	//byte
            'int0' => 0,		//int
            'score' => $data->ranked_score,			//long 	score
            'accuracy' => $this->getAccuracy($player, $status['Mode']),	//float accuracy
            'playcount' => $data->playcount,			//int playcount
            'experience' => $data->total_score,			//long 	experience
            'int1' => $this->getUserRank($player, $status['Mode']),	//int 	global rank?
            'pp' => $data->pp,			//short	pp 				if set, will use?
        );
    }

    public function getDataFromName($name)
    {
        return User::where('name', $name)->first();
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
        $redis->set(sprintf("CurrentlyLoggedInID:%d", $user->id), $user->id);
        $redis->expire(sprintf("CurrentlyLoggedIn:%s", $token), 60);
        $redis->expire(sprintf("CurrentlyLoggedInID:%d", $user->id), 30);
    }

    public function updateToken($token, $userID)
    {
        $redis = Redis::connection();
        $redis->expire(sprintf("CurrentlyLoggedIn:%s", $token), 60);
        $redis->expire(sprintf("CurrentlyLoggedInID:%d", $userID), 30);
    }

    public function expireToken($token)
    {
        $redis = Redis::connection();
        $redis->del(sprintf("CurrentlyLoggedIn:%s", $token));
    }

    public function getExp($player)
    {
        $currentScore = $player->OsuUserStats->total_score;
        $level = $this->getLevel($player);
        if ($level <= 100) {
            if ($level >= 2) {
                $result = 5000 / 3 * (4 * bcpow($level, 3, 0) - 3 * bcpow($level, 2, 0) - $level) + 1.25 * bcpow(1.8, $level - 60, 0);
            }
            else {
                $result = 0;
            }
        }
        else {
            $result = 26931190829 + 100000000000 * ($level - 100);
        }
        return ($result - $currentScore);
    }

    public function getAccuracy($player, $mode = 0)
    {
        switch($mode)
        {
            case 0:
                $data = $player->OsuUserStats;
                break;
            case 1:
                $data = $player->TaikoUserStats;
                break;
            case 2:
                $data = $player->CTBUserStats;
                break;
            case 3:
                $data = $player->ManiaUserStats;
                break;
        }
        $totalHits = ($data->count50 + $data->count100 + $data->count300 + $data->countmiss) * 300;
        $hits = $data->count50 * 50 + $data->count100 * 100 + $data->count300 * 300;
        if($hits && $totalHits != 0) {
            return $hits / $totalHits;
        } else {
            return 0;
        }
    }
}