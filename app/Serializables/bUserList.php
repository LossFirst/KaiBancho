<?php

namespace App\Serializables;

use App\Libraries\PhpBinaryReader\BinaryReader;

class bUserList
{
    public $userList;

    public function getOnlineStats(BinaryReader &$stream)
    {
        $stream->readBytes(2);
        while(true)
        {
            if(!$stream->canReadBytes(4)) break;
            $temp = $stream->readUInt32();
            if($temp == 0) break;
            $this->userList[] = $temp;
        }
    }
}