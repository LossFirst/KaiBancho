<?php

namespace App\Serializables;

use App\Libraries\PhpBinaryReader\BinaryReader;
use Log;

class bChat
{
    public $message;
    public $reciever;
    public $other;

    public function bChat(BinaryReader &$stream)
    {
        $this->other = $stream->readULEB128();
        $this->message = $stream->readULEB128();
        $this->reciever = $stream->readULEB128();
    }
}