<?php
namespace App\Libraries;

use Redis;
use Log;

/**
 * Class RedisPacket
 * @package App\Libraries
 */
class RedisPacket {
    /**
     * @param $userID
     * @param string $packetID
     * @return array
     */
    public function GetPackets($userID, $packetID = "")
    {
        $redis = Redis::connection();
        $packet = new Packet();
        $redisPackets = array();
        if(empty($packetID))
        {
            $values = $redis->keys(sprintf("packet:%d:*", $userID));
        } else {
            $values = $redis->keys(sprintf("packet:%d:%d:*", $userID, $packetID));
        }
        if(!empty($values))
        {
            foreach($values as $value)
            {
                $redisPacket = json_decode($redis->get($value));
                $redisPackets = array_merge($redisPackets, $packet->create($redisPacket[0], $redisPacket[1]));
            }
            $redis->del($values);
        }
        return $redisPackets;
    }

    /**
     * @param $userID
     * @param $packetID
     * @param $packetData
     * @return bool
     */
    public function CreatePacket($userID, $packetID, $packetData)
    {
        $redis = Redis::connection();
        $player = new Player();
        $timestamp = $this->getTimestamp(true);
        if(!$player->isIDOnline($userID)) {
            return false;
        }
        $redis->set(sprintf("packet:%d:%d:%s", $userID, $packetID, $timestamp), json_encode(array($packetID, $packetData)));
        $redis->expire(sprintf("packet:%d:%d:%s", $userID, $packetID, $timestamp), 30);
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
}
