<?php namespace App\Libraries; use Carbon\Carbon; use Redis; use Log; class RedisPacket {
    public function GetPackets($userID)
    {
        $redis = Redis::connection();
        $packet = new Packet();
        $redisPackets = array();
        $values = $redis->keys(sprintf("packet:%d:*", $userID));
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
    public function CreatePacket($userID, $packet, $packetData)
    {
        $redis = Redis::connection();
        $player = new Player();
        $timestamp = strtotime(Carbon::now());
        $random = rand(1,1000);
        if(!$player->isIDOnline($userID)) {
            return false;
        }
        $redis->set(sprintf("packet:%d:%d:%s", $userID, $random, $timestamp), json_encode(array($packet, $packetData)));
        $redis->expire(sprintf("packet:%d:%d:%s", $userID, $random, $timestamp), 30);
        return true;
    }
}
