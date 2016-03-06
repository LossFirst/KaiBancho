<?php

namespace App\Libraries\PhpBinaryReader\Type;

use App\Libraries\PhpBinaryReader\BinaryReader;
use App\Libraries\PhpBinaryReader\BinaryWriter;
use App\Libraries\PhpBinaryReader\BitMask;
use App\Libraries\PhpBinaryReader\Endian;

class Int16 implements TypeInterface
{
    /**
     * @var string
     */
    private $endianBig = 'n';
    /**
     * @var string
     */
    private $endianLittle = 'v';
    /**
     * Returns an Unsigned 16-bit Integer
     *
     * @param  \App\Libraries\PhpBinaryReader\BinaryReader $br
     * @param  null                          $length
     * @return int
     * @throws \OutOfBoundsException
     */
    public function read(BinaryReader &$br, $length = null)
    {
        if (!$br->canReadBytes(2)) {
            throw new \OutOfBoundsException('Cannot read 16-bit int, it exceeds the boundary of the file');
        }
        $endian = $br->getEndian() == Endian::ENDIAN_BIG ? $this->endianBig : $this->endianLittle;
        $segment = $br->readFromHandle(2);
        $data = unpack($endian, $segment);
        $data = $data[1];
        if ($br->getCurrentBit() != 0) {
            $data = $this->bitReader($br, $data);
        }
        return $data;
    }
    /**
     * Returns a Signed 16-bit Integer
     *
     * @param  \App\Libraries\PhpBinaryReader\BinaryReader $br
     * @return int
     */
    public function readSigned(&$br)
    {
        $this->setEndianBig('s');
        $this->setEndianLittle('s');
        $value = $this->read($br);
        $this->setEndianBig('n');
        $this->setEndianLittle('v');
        $endian = new Endian();
        if ($br->getMachineByteOrder() != Endian::ENDIAN_LITTLE && $br->getEndian() == Endian::ENDIAN_LITTLE) {
            return $endian->convert($value);
        } else {
            return $value;
        }
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
        $hiBits = ($br->getNextByte() & $hiMask) << 8;
        $miBits = ($data & 0xFF00) >> (8 - $br->getCurrentBit());
        $loBits = ($data & $loMask);
        $br->setNextByte($data & 0xFF);
        return $hiBits | $miBits | $loBits;
    }
    /**
     * @param string $endianBig
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
     * @param string $endianLittle
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
        $bw->inputHandle = array_merge($bw->inputHandle, unpack('C*', pack('v*', $value)));
    }
}