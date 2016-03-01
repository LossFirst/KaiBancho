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
    private $stream;

    public function __construct(BinaryReader &$stream)
    {
        $this->stream = $stream;
    }

    public function readMessage()
    {
        $this->other = $this->stream->readULEB128();
        $this->message = $this->stream->readULEB128();
        $this->reciever = $this->stream->readULEB128();
    }

    public function readChannel()
    {
        $this->channel = $this->stream->readULEB128();
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