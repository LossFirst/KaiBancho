<?php

namespace App\Libraries\PhpBinaryReader\Type;

use App\Libraries\PhpBinaryReader\BinaryReader;
use App\Libraries\PhpBinaryReader\Exception\InvalidDataException;

class String2 implements TypeInterface
{
    /**
     * @param  \App\Libraries\PhpBinaryReader\BinaryReader $br
     * @param  int                           $length
     * @return string
     * @throws \OutOfBoundsException
     * @throws InvalidDataException
     */
    public function read(BinaryReader &$br, $length)
    {
        if (!is_int($length)) {
            throw new InvalidDataException('The length parameter must be an integer');
        }
        if (!$br->canReadBytes($length)) {
            throw new \OutOfBoundsException('Cannot read string, it exceeds the boundary of the file');
        }
        $str = $br->readFromHandle($length);
        return $str;
    }
    /**
     * @param  \App\Libraries\PhpBinaryReader\BinaryReader $br
     * @param  int                           $length
     * @return string
     */
    public function readAligned(BinaryReader &$br, $length)
    {
        $br->align();
        return $this->read($br, $length);
    }
}
