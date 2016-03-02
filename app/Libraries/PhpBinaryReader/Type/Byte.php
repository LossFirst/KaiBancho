<?php

namespace App\Libraries\PhpBinaryReader\Type;

use App\Libraries\PhpBinaryReader\BinaryReader;
use App\Libraries\PhpBinaryReader\Exception\InvalidDataException;
use App\Libraries\PhpBinaryReader\BinaryWriter;

class Byte implements TypeInterface
{
    /**
     * Returns an variable number of bytes
     *
     * @param  \App\Libraries\PhpBinaryReader\BinaryReader $br
     * @param  int|null                      $length
     * @return string
     * @throws \OutOfBoundsException
     * @throws InvalidDataException
     */
    public function read(BinaryReader &$br, $length = null)
    {
        if (!is_int($length)) {
            throw new InvalidDataException('The length parameter must be an integer');
        }
        $br->align();
        if (!$br->canReadBytes($length)) {
            throw new \OutOfBoundsException('Cannot read bytes, it exceeds the boundary of the file');
        }
        $segment = $br->readFromHandle($length);
        return $segment;
    }

    /**
     * @param BinaryWriter $bw
     * @param $value
     */
    public function write(BinaryWriter &$bw, $value)
    {
        $bw->inputHandle = array_merge($bw->inputHandle, array($value));
	}
}
