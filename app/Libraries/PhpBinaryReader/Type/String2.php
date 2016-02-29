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

    /**
     * @param BinaryReader $br
     * @return string
     */
    public function readULEB128(BinaryReader &$br)
    {
        $isLong = false;
        $string = "";
        while(true)
        {
            $pos = $br->getPosition();
            $bit = $br->readUInt8();
            if(!$isLong)
            {
                if($bit != 11) throw new InvalidDataException('The string isn\'t a Unsigned LEB128');
                $length = $br->readUInt8();
                if($length == 0) break;
                $pos = $br->getPosition();
                $checkNext = $br->readUInt8();
                if($checkNext >= 32) // TODO: actually check to see if next byte is the multiple and shift it. Otherwise max length can only be 255 * 31 till it crashes the client
                {
                    $br->setPosition($pos);
                    $string = $br->readString($length);
                    break;
                }
            } else {
                if($bit == 11) {$br->setPosition($pos); break;}
                $string .= chr($bit);
            }
            $isLong = true;
        }

        return $string;
    }
}
