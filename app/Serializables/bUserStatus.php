<?php

namespace App\Serializables;

use App\Libraries\PhpBinaryReader\BinaryReader;

class bUserStatus
{
    public $beatmap;
    public $beatmapHash;
    public $something;
    public $mods;
    public $playMode;
    public $status;

    public function bUserStatus(BinaryReader &$stream)
    {
        $this->status = ord($stream->readBytes(1));
        $this->beatmap = $stream->readULEB128();
        $this->beatmapHash = $stream->readULEB128();
        $this->mods = $stream->readUInt32();
        $this->playMode = $stream->readUInt8();
        $this->something = $stream->readUInt32();
    }
}