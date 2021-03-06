<?php

namespace App\Libraries\PhpBinaryReader\Type;

use App\Libraries\PhpBinaryReader\BinaryReader;
use App\Libraries\PhpBinaryReader\BinaryWriter;
use App\Libraries\PhpBinaryReader\BitMask;
use App\Libraries\PhpBinaryReader\Endian;

class Int32 implements TypeInterface
{
    /**
     * @var string
     */
    private $endianBig = 'N';
    /**
     * @var string
     */
    private $endianLittle = 'V';
    /**
     * Returns an Unsigned 32-bit Integer
     *
     * @param  BinaryReader $br
     * @param  null                          $length
     * @return int
     * @throws \OutOfBoundsException
     */
    public function read(BinaryReader &$br, $length = null)
    {
        if (!$br->canReadBytes(4)) {
            throw new \OutOfBoundsException('Cannot read 32-bit int, it exceeds the boundary of the file');
        }
        $endian = $br->getEndian() == Endian::ENDIAN_BIG ? $this->endianBig : $this->endianLittle;
        $segment = $br->readFromHandle(4);
        $data = unpack($endian, $segment);
        $data = $data[1];
        if ($br->getCurrentBit() != 0) {
            $data = $this->bitReader($br, $data);
        }
        return $data;
    }
    /**
     * Returns a Signed 32-Bit Integer
     *
     * @param  BinaryReader $br
     * @return int
     */
    public function readSigned(&$br)
    {
        $this->setEndianBig('l');
        $this->setEndianLittle('l');
        $value = $this->read($br);
        $this->setEndianBig('N');
        $this->setEndianLittle('V');
        if ($br->getMachineByteOrder() != Endian::ENDIAN_LITTLE && $br->getEndian() == Endian::ENDIAN_LITTLE) {
            $endian = new Endian();
            return $endian->convert($value);
        } else {
            return $value;
        }
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
        $miBits = ($data & 0xFFFFFF00) >> (8 - $br->getCurrentBit());
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
        $bw->inputHandle = array_merge($bw->inputHandle, unpack('C*', pack('V*', $value)));
    }
}
