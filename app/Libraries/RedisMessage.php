<?php

namespace App\Libraries;

use Carbon\Carbon;
use Redis;
use Log;

class RedisMessage
{
    public function GetMessage($userID)
    {
        $redis = Redis::connection();
        $packet = new Packet();
        $chatMessage = array();
        $values = $redis->keys(sprintf("chat:%d:*", $userID));
        if(!empty($values))
        {
            foreach($values as $value)
            {
                $chatMessage = array_merge($chatMessage, $packet->create(07, json_decode($redis->get($value))));
            }
            $redis->del($values);
        }
        return $chatMessage;
    }

    public function SendMessage($user, $data)
    {
        $redis = Redis::connection();
        $packet = new Packet();
        $player = new Player();
        $messageArray = array();
        foreach (array_slice($data, 11) as $item) {
            if ($item == 11) {
                break;
            }
            array_push($messageArray, $item);
        }
        $message = implode(array_map("chr", $messageArray));
        $toChannelArray = array();
        foreach (array_slice($data, 11 + count($messageArray) + 2) as $item) {
            if ($item == 0) {
                break;
            }
            array_push($toChannelArray, $item);
        }
        $channel = implode(array_map("chr", $toChannelArray));

        if($this->isCommand($messageArray))
        {
            return $this->command($messageArray, $user);
        }

        $timestamp = strtotime(Carbon::now());
        $random = rand(1,1000);
        $redis->set(sprintf("chat:%d:%d:%s", $user->id, $random, $timestamp), json_encode(array($user->name, $message, $channel, $user->id)));
        $redis->expire(sprintf("chat:%d:%d:%s", $user->id, $random, $timestamp), 30);
    }

    public function isCommand($messageArray)
    {
        if($messageArray[0] == 33)
        {
            return true;
        } else {
            return false;
        }
    }

    public function command($messageArray, $user)
    {
        $redis = Redis::connection();
        $command = array();
        foreach($messageArray as $item)
        {
            if($item != 32)
            {
                array_push($command, $item);
            }
        }

        switch(implode(array_map("chr", $command)))
        {
            case "!roll":
                $data = json_encode(array("KaiBanchoo", sprintf("You rolled a %d", rand(1,100)), $user->name, 2));
                break;
            default:
                $data = json_encode(array("KaiBanchoo", sprintf("The command %s doesn't exist", implode(array_map("chr", $command))), $user->name, 2));
                break;
        }
        $timestamp = strtotime(Carbon::now());
        $random = rand(1,1000);
        $redis->set(sprintf("chat:%d:%d:%s", $user->id, $random, $timestamp), $data);
        $redis->expire(sprintf("chat:%d:%d:%s", $user->id, $random, $timestamp), 30);
        return array();
    }
}