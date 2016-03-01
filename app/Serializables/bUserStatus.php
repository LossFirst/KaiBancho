<?php

namespace App\Serializables;

use App\Libraries\PhpBinaryReader\BinaryReader;
use Redis;

class bUserStatus
{
    public $beatmap = '';
    public $beatmapHash = '';
    public $something = 0;
    public $mods = 0;
    public $playMode = 0;
    public $status = 0;
    private $stream;

    public function __construct(BinaryReader &$stream)
    {
        $this->stream = $stream;
    }

    public function readUserStatus()
    {
        $this->status = ord($this->stream->readBytes(1));
        $this->beatmap = $this->stream->readULEB128();
        $this->beatmapHash = $this->stream->readULEB128();
        $this->mods = $this->stream->readUInt32();
        $this->playMode = $this->stream->readUInt8();
        $this->something = $this->stream->readUInt32();
    }

    public function updateUserStatus($userID)
    {
        $redis = Redis::connection();
        $key = sprintf("UserInfo:%d", $userID);
        $redis->hmset($key, [
            'status' => $this->status,
            'beatmap' => $this->beatmap,
            'beatmapHash' => $this->beatmapHash,
            'mods' => $this->mods,
            'playMode' => $this->playMode,
            'something' => $this->something
        ]);
    }
}