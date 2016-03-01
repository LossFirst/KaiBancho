<?php

namespace App\Libraries\PhpBinaryReader\Type;

use App\Libraries\Helper;
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

    /**
     * @param BinaryReader $br
     * @return string
     */
    public function readULEB128(BinaryReader &$br)
    {
        $helper = new Helper();
        $string = "";

        $byte = ord($br->readBytes(1)); // Is it 11?
        if($byte != 11) throw new InvalidDataException('The string isn\'t an Unsigned LEB128');
        $lengthByte = ord($br->readBytes(1));
        if($lengthByte == 0) return $string;
        $pos = $br->getPosition();
        $shiftByte = ord($br->readBytes(1));
        $length = 0;
        $shift = $helper->udecode(chr($lengthByte).chr($shiftByte), $length);
        if($shift == 1) $br->setPosition($pos);
        $string = $br->readString($length);
        return $string;
    }
}
