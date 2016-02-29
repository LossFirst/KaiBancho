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
        $Loop = false;
        while(true)
        {
            $bit = $stream->readUInt8();
            if($bit == 11 && $Loop)
            {
                $length = $stream->readUInt8();
                $this->reciever = ($length != 0)?$stream->readString($length):"";
                break;
            }
            if($Loop)
            {
                $this->message .= chr($bit);
            }
            if(!$Loop)
            {
                $length = $stream->readUInt8();
                $pos = $stream->getPosition();
                $test = $stream->readUInt8();
                if($test != 2)
                {
                    $stream->setPosition($pos);
                    $this->message = ($length != 0)?$stream->readString($length):"";
                }
            }
            $Loop = true;
        }
    }
}