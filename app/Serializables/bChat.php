<?php

namespace App\Serializables;

use App\Libraries\PhpBinaryReader\BinaryReader;
use Log;
use Redis;

class bChat
{
    public $message = "";
    public $reciever = "";
    public $other = "";
    public $channel = "";

    public function readMessage(BinaryReader &$stream)
    {
        $this->other = $stream->readULEB128();
        $this->message = $stream->readULEB128();
        $this->reciever = $stream->readULEB128();
    }

    public function readChannel(BinaryReader &$stream)
    {
        $this->channel = $stream->readULEB128();
    }

    public function joinChannel($userID)
    {
        $redis = Redis::connection();
        $key = sprintf("UserInfo:%d", $userID);
        $redis->sadd($key, $this->channel);
        log::info($redis->smembers($key));
    }

    public function leaveChannel($userID)
    {
        $redis = Redis::connection();
        $key = sprintf("UserInfo:%d", $userID);
        $redis->srem($key, $this->channel);
        log::info($redis->smembers($key));
    }
}