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

    public function readUserStatus(BinaryReader &$stream)
    {
        $this->status = ord($stream->readBytes(1));
        $this->beatmap = $stream->readULEB128();
        $this->beatmapHash = $stream->readULEB128();
        $this->mods = $stream->readUInt32();
        $this->playMode = $stream->readUInt8();
        $this->something = $stream->readUInt32();
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