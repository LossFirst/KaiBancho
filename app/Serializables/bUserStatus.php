<?php

namespace App\Serializables;

class bUserStatus
{
    public $beatmap;
    public $beatmapHash;
    public $something;
    public $mods;
    public $playMode;
    public $status;

    public function bUserStatus(&$stream)
    {
        $this->status = $stream->readUBits(8);
        $stream->readUBits(8);
        $beatmapLength = $stream->readUInt8();
        $this->beatmap = ($beatmapLength != 0)?$stream->readString($beatmapLength):"";
        $stream->readUBits(8);
        $hashLength = $stream->readUInt8();
        $this->beatmapHash = ($hashLength != 0)?$stream->readString($hashLength):"";
        $stream->readUBits(32);
        $this->playMode = $stream->readUInt8();
        $this->something = $stream->readUInt32();
    }
}