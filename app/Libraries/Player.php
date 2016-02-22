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
            'playerRank' => 0,
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
        $redis->expire(sprintf("CurrentlyLoggedIn:%s", $token), 30);
        $redis->expire(sprintf("CurrentlyLoggedInID:%d", $user->id), 30);
    }

    public function updateToken($token, $userID)
    {
        $redis = Redis::connection();
        $redis->expire(sprintf("CurrentlyLoggedIn:%s", $token), 30);
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

    public function getLevel($player)
    {
        $t = $player->OsuUserStats->total_score;
        switch(true)
        {
            case($t < 30000): $level = 1; break;
            case($t < 100000): $level = 2; break;
            case($t < 210000): $level = 3; break;
            case($t < 360000): $level = 4; break;
            case($t < 550000): $level = 5; break;
            case($t < 780000): $level = 6; break;
            case($t < 1050000): $level = 7; break;
            case($t < 1360000): $level = 8; break;
            case($t < 1710000): $level = 9; break;
            case($t < 2100000): $level = 10; break;
            case($t < 2530000): $level = 11; break;
            case($t < 3000000): $level = 12; break;
            case($t < 3510000): $level = 13; break;
            case($t < 4060000): $level = 14; break;
            case($t < 4650000): $level = 15; break;
            case($t < 5280000): $level = 16; break;
            case($t < 5950000): $level = 17; break;
            case($t < 6660000): $level = 18; break;
            case($t < 7410000): $level = 19; break;
            case($t < 8200000): $level = 20; break;
            case($t < 9030000): $level = 21; break;
            case($t < 9900000): $level = 22; break;
            case($t < 10810000): $level = 23; break;
            case($t < 11760000): $level = 24; break;
            case($t < 12750000): $level = 25; break;
            case($t < 13780000): $level = 26; break;
            case($t < 14850000): $level = 27; break;
            case($t < 15960000): $level = 28; break;
            case($t < 17110000): $level = 29; break;
            case($t < 18300000): $level = 30; break;
            case($t < 19530000): $level = 31; break;
            case($t < 20800000): $level = 32; break;
            case($t < 22110000): $level = 33; break;
            case($t < 23460000): $level = 34; break;
            case($t < 24850000): $level = 35; break;
            case($t < 26280000): $level = 36; break;
            case($t < 27750000): $level = 37; break;
            case($t < 29260000): $level = 38; break;
            case($t < 30810000): $level = 39; break;
            case($t < 32400000): $level = 40; break;
            case($t < 34030000): $level = 41; break;
            case($t < 35700000): $level = 42; break;
            case($t < 37410000): $level = 43; break;
            case($t < 39160000): $level = 44; break;
            case($t < 40950000): $level = 45; break;
            case($t < 42780000): $level = 46; break;
            case($t < 44650000): $level = 47; break;
            case($t < 46560000): $level = 48; break;
            case($t < 48510000): $level = 49; break;
            case($t < 50500000): $level = 50; break;
            case($t < 52530000): $level = 51; break;
            case($t < 54600000): $level = 52; break;
            case($t < 56710000): $level = 53; break;
            case($t < 58860000): $level = 54; break;
            case($t < 61050000): $level = 55; break;
            case($t < 63280000): $level = 56; break;
            case($t < 65550000): $level = 57; break;
            case($t < 67860000): $level = 58; break;
            case($t < 70210001): $level = 59; break;
            case($t < 72600000): $level = 60; break;
            case($t < 75030002): $level = 61; break;
            case($t < 77500002): $level = 62; break;
            case($t < 80010006): $level = 63; break;
            case($t < 82560010): $level = 64; break;
            case($t < 85150020): $level = 65; break;
            case($t < 87780033): $level = 66; break;
            case($t < 90450061): $level = 67; break;
            case($t < 93160110): $level = 68; break;
            case($t < 95910198): $level = 69; break;
            case($t < 98700356): $level = 70; break;
            case($t < 101530642): $level = 71; break;
            case($t < 104401157): $level = 72; break;
            case($t < 107312082): $level = 73; break;
            case($t < 110263747): $level = 74; break;
            case($t < 113256746): $level = 75; break;
            case($t < 116292145): $level = 76; break;
            case($t < 119371858): $level = 77; break;
            case($t < 122499346): $level = 78; break;
            case($t < 125680823): $level = 79; break;
            case($t < 128927482): $level = 80; break;
            case($t < 132259467): $level = 81; break;
            case($t < 135713043): $level = 82; break;
            case($t < 139353476): $level = 83; break;
            case($t < 143298258): $level = 84; break;
            case($t < 147758866): $level = 85; break;
            case($t < 153115958): $level = 86; break;
            case($t < 160054726): $level = 87; break;
            case($t < 169808505): $level = 88; break;
            case($t < 184597311): $level = 89; break;
            case($t < 208417160): $level = 90; break;
            case($t < 248460887): $level = 91; break;
            case($t < 317675596): $level = 92; break;
            case($t < 439366075): $level = 93; break;
            case($t < 655480935): $level = 94; break;
            case($t < 1041527682): $level = 95; break;
            case($t < 1733419828): $level = 96; break;
            case($t < 2975801691): $level = 97; break;
            case($t < 5209033043): $level = 98; break;
            case($t < 9225761478): $level = 99; break;
            case($t < 100000000001): $level = 100; break;
            default: $level = 0; break;
        }
        return $level;
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