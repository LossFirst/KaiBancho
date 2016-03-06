<?php

namespace App\Libraries\PhpBinaryReader\Type;

use App\Libraries\PhpBinaryReader\BinaryReader;
use App\Libraries\PhpBinaryReader\BinaryWriter;
use App\Libraries\PhpBinaryReader\Endian;
use App\Libraries\PhpBinaryReader\BitMask;
use Log;

/**
 * Class Int64
 * @package App\Libraries\PhpBinaryReader\Type
 */
class Int64
{
    /**
     * @var string
     */
    private $endianBig = 'J';

    /**
     * @var string
     */
    private $endianLittle = 'P';

    /**
     * @param BinaryReader $br
     * @param null $length
     * @return array
     */
    public function read(BinaryReader &$br, $length = null)
    {
        if (!$br->canReadBytes(8)) {
            throw new \OutOfBoundsException('Cannot read 64-bit int, it exceeds the boundary of the file');
        }
        $endian = $br->getEndian() == Endian::ENDIAN_BIG ? $this->endianBig : $this->endianLittle;
        $segment = $br->readFromHandle(8);
        $data = unpack($endian, $segment);
        $data = $data[1];
        if ($br->getCurrentBit() != 0) {
            $data = $this->bitReader($br, $data);
        }
        return $data;
    }

    /**
     * @param  BinaryReader $br
     * @param  int                           $data
     * @return int
     */
    private function bitReader(&$br, $data)
    {
        $bitmask = new BitMask();
        $loMask = $bitmask->getMask($br->getCurrentBit(), BitMask::MASK_LO);
        $hiMask = $bitmask->getMask($br->getCurrentBit(), BitMask::MASK_HI);
        $hiBits = ($br->getNextByte() & $hiMask) << 24;
        $miBits = ($data & 0xFFFF00) >> (8 - $br->getCurrentBit());
        $loBits = ($data & $loMask);
        $br->setNextByte($data & 0xFF);
        return $hiBits | $miBits | $loBits;
    }

    /**
     * @param $endianBig
     */
    public function setEndianBig($endianBig)
    {
        $this->endianBig = $endianBig;
    }

    /**
     * @return string
     */
    public function getEndianBig()
    {
        return $this->endianBig;
    }

    /**
     * @param $endianLittle
     */
    public function setEndianLittle($endianLittle)
    {
        $this->endianLittle = $endianLittle;
    }

    /**
     * @return string
     */
    public function getEndianLittle()
    {
        return $this->endianLittle;
    }

    /**
     * @param BinaryWriter $bw
     * @param $value
     */
    public function write(BinaryWriter &$bw, $value)
    {
        $bw->inputHandle = array_merge($bw->inputHandle, unpack('C*', pack('P', $value)));
    }
}