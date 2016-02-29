<?php

namespace App\Serializables;

use Log;

class bChat
{
    public $message;
    public $reciever;
    public $other;

    public function bChat(&$stream)
    {
        $stream->readUBits(8);
        $otherLength = $stream->readUInt8();
        $this->other = ($otherLength != 0)?$stream->readString($otherLength):"";
        $over127 = false;
        while(true)
        {
            $bit = $stream->readUInt8();
            if(!$over127)
            {
                $length = $stream->readUInt8();
                $pos = $stream->getPosition();
                if($stream->readUInt8() != 2)
                {
                    $stream->setPosition($pos);
                    $this->message = ($length != 0)?$stream->readString($length):"";
                    $stream->readUInt8();
                    break;
                }
            } else {
                if($bit == 11) break;
                $this->message .= chr($bit);
            }
            $over127 = true;
        }
        $length = $stream->readUInt8();
        $this->reciever = ($length != 0)?$stream->readString($length):"";
    }
}