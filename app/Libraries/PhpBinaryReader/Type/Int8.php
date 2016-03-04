<?php

namespace App\Libraries\PhpBinaryReader\Type;

use App\Libraries\PhpBinaryReader\BinaryReader;
use App\Libraries\PhpBinaryReader\BinaryWriter;
use App\Libraries\PhpBinaryReader\BitMask;

class Int8 implements TypeInterface
{
    /**
     * @var string
     */
    private $endian = 'C';
    /**
     * Returns an Unsigned 8-bit Integer (aka a single byte)
     *
     * @param  \App\Libraries\PhpBinaryReader\BinaryReader $br
     * @param  null                          $length
     * @return int
     * @throws \OutOfBoundsException
     */
    public function read(BinaryReader &$br, $length = null)
    {
        if (!$br->canReadBytes(1)) {
            throw new \OutOfBoundsException('Cannot read 8-bit int, it exceeds the boundary of the file');
        }
        $segment = $br->readFromHandle(1);
        $data = unpack($this->endian, $segment);
        $data = $data[1];
        if ($br->getCurrentBit() != 0) {
            $data = $this->bitReader($br, $data);
        }
        return $data;
    }
    /**
     * Returns a Signed 8-bit Integer (aka a single byte)
     *
     * @param  \App\Libraries\PhpBinaryReader\BinaryReader $br
     * @return int
     */
    public function readSigned(&$br)
    {
        $this->setEndian('c');
        $value = $this->read($br);
        $this->setEndian('C');
        return $value;
    }
    /**
     * @param  \App\Libraries\PhpBinaryReader\BinaryReader $br
     * @param  int                           $data
     * @return int
     */
    private function bitReader(&$br, $data)
    {
        $bitmask = new BitMask();
        $loMask = $bitmask->getMask($br->getCurrentBit(), BitMask::MASK_LO);
        $hiMask = $bitmask->getMask($br->getCurrentBit(), BitMask::MASK_HI);
        $hiBits = $br->getNextByte() & $hiMask;
        $loBits = $data & $loMask;
        $br->setNextByte($data);
        return $hiBits | $loBits;
    }
    /**
     * @param string $endian
     */
    public function setEndian($endian)
    {
        $this->endian = $endian;
    }
    /**
     * @return string
     */
    public function getEndian()
    {
        return $this->endian;
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