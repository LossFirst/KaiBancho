<?php

namespace App\Libraries;

use Redis;
use Log;

/**
 * Class RedisMessage
 * @package App\Libraries
 */
class RedisMessage
{
    /**
     * @param $userID
     * @return array
     */
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
                $chatMessage = array_merge($chatMessage, $packet->create(Packets::OUT_SendChatMSG, json_decode($redis->get($value))));
            }
            $redis->del($values);
        }
        return $chatMessage;
    }

    /**
     * @param $user
     * @param $messageData
     * @return bool
     */
    public function SendMessage($user, $messageData)
    {
        $redis = Redis::connection();
        $player = new Player();

        $timestamp = $this->getTimestamp(true);
        $isChannel = strpos($messageData['Channel'],'#');
        if(substr($messageData['Message'], 0, 1) == "!")
        {
            $this->command($messageData['Message'], $user, ($isChannel === false) ? null : $messageData['Channel']);
            return true;
        }

        if($isChannel === false)
        {
            $toUser = $player->getDataFromName($messageData['Channel']);
            $redis->set(sprintf("chat:%d:%s", $toUser->id, $timestamp), json_encode(array($user->name, $messageData['Message'], $messageData['Channel'], $user->id)));
            $redis->expire(sprintf("chat:%d:%s", $toUser->id, $timestamp), 30);
            return true;
        }

        foreach($player->getAllIDs($player->getAllTokens()) as $id)
        {
            if($id != $user->id) {
                $redis->set(sprintf("chat:%d:%s", $id, $timestamp), json_encode(array($user->name, $messageData['Message'], $messageData['Channel'], $user->id)));
                $redis->expire(sprintf("chat:%d:%s", $id, $timestamp), 30);
            }
        }
        return true;
    }

    /**
     * @param bool $asString
     * @return float|string
     */
    function getTimestamp($asString=false){
        $seconds = microtime(true); // false = int, true = float
        $stamp = round($seconds * 10000);
        if($asString == true){
            return sprintf('%.0f', $stamp);
        } else {
            return $stamp;
        }
    }

    /**
     * @param $message
     * @return bool
     */
    function isCommand($message)
    {
        if(substr($message, 0) == "!")
        {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $message
     * @param $user
     * @param $channel
     */
    function command($message, $user, $channel)
    {
        $command = explode(' ', $message);
        $returnTo = (is_null($channel)) ? $user->name : $channel ;
        $return = array('Channel' => $returnTo);
        switch($command[0])
        {
            case "!roll":
                $data = sprintf("You rolled a %d", rand(1, (array_key_exists(1 ,$command) && (strlen((string)intval($command[1]))) > 1) ? intval($command[1]) : 100));
                $return = array_merge($return, array('Message' => $data));
                $this->SendMessage((object)array('id' => -1, 'name' => 'KaiBancho'), $return);
                break;
            default:
                $data = sprintf("The command %s doesn't exist", $command[0]);
                $return = array_merge($return, array('Message' => $data));
                $this->SendMessage((object)array('id' => -1, 'name' => 'KaiBancho'), $return);
                break;
        }
    }
}