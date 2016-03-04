<?php

namespace App\Serializables;

use App\Libraries\PhpBinaryReader\BinaryReader;

class bUserList
{
    public $userList;
    private $stream;

    /**
     * bUserList constructor.
     * @param BinaryReader $stream
     */
    public function __construct(BinaryReader &$stream)
    {
        $this->stream = $stream;
    }

    public function getOnlineStats()
    {
        $this->stream->readBytes(2);
        while(true)
        {
            if(!$this->stream->canReadBytes(4)) break;
            $temp = $this->stream->readUInt32();
            if($temp == 0) break;
            $this->userList[] = $temp;
        }
    }
}