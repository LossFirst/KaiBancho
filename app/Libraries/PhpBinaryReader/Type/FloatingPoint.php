<?php

namespace App\Libraries\PhpBinaryReader\Type;

use App\Libraries\PhpBinaryReader\BinaryReader;
use App\Libraries\PhpBinaryReader\BinaryWriter;
use App\Libraries\PhpBinaryReader\Endian;
use App\Libraries\PhpBinaryReader\BitMask;

class FloatingPoint
{
    public function write(BinaryWriter &$bw, $value)
    {
        $bw->inputHandle = array_merge($bw->inputHandle, unpack('C*', pack('f', $value)));
    }
}