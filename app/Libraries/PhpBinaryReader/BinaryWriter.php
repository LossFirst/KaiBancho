<?php

namespace App\Libraries\PhpBinaryReader;

use App\Libraries\Helper;
use App\Libraries\PhpBinaryReader\Exception\InvalidDataException;
use App\Libraries\PhpBinaryReader\Type\Bit;
use App\Libraries\PhpBinaryReader\Type\Byte;
use App\Libraries\PhpBinaryReader\Type\FloatingPoint;
use App\Libraries\PhpBinaryReader\Type\Int8;
use App\Libraries\PhpBinaryReader\Type\Int16;
use App\Libraries\PhpBinaryReader\Type\Int32;
use App\Libraries\PhpBinaryReader\Type\Int64;
use App\Libraries\PhpBinaryReader\Type\String2;

/**
 * Class BinaryWriter
 * @package App\Libraries\PhpBinaryReader
 */
class BinaryWriter
{
    /**
     * @var array|resource
     */
    public $inputHandle = array();
    /**
     * @var int
     */
    private $currentBit;
    /**
     * @var mixed
     */
    private $endian;
    /**
     * @var Type\Byte
     */
    private $byteWriter;
    /**
     * @var Type\Bit
     */
    private $bitWriter;
    /**
     * @var Type\String
     */
    private $stringWriter;
    /**
     * @var Type\Int8
     */
    private $int8Writer;
    /**
     * @var Type\Int16
     */
    private $int16Writer;
    /**
     * @var Type\Int32
     */
    private $int32Writer;

    /**
     * @var Type\Int64
     */
    private $int64Writer;

    /**
     * @var Type\FloatingPoint
     */
    private $floatingPointWriter;

    /**
     * @var Helper
     */
    private $helper;
    /**
     * @param  int|string                $endian
     * @throws \InvalidArgumentException
     */
    public function __construct($endian = Endian::ENDIAN_LITTLE)
    {
        $this->setEndian($endian);
        $this->bitWriter = new Bit();
        $this->stringWriter = new String2();
        $this->byteWriter = new Byte();
        $this->int8Writer = new Int8();
        $this->int16Writer = new Int16();
        $this->int32Writer = new Int32();
        $this->int64Writer = new Int64();
        $this->floatingPointWriter = new FloatingPoint();
        $this->helper = new Helper();
    }

    /**
     * @param $bytes
     */
    public function writeBytes($bytes)
    {
        $this->byteWriter->write($this, $bytes);
    }

    /**
     * @param $value
     */
    public function writeUInt8($value)
    {
        $this->int8Writer->write($this, $value);
    }

    /**
     * @param $value
     */
    public function writeUInt16($value)
    {
        $this->int16Writer->write($this, $value);
    }

    /**
     * @param $value
     */
    public function writeUInt32($value)
    {
        $this->int32Writer->write($this, $value);
    }

    /**
     * @param $value
     */
    public function writeUInt64($value)
    {
        $this->int64Writer->write($this, $value);
    }

    /**
     * @param $value
     */
    public function writeString($value)
    {
        $this->stringWriter->write($this, $value);
    }

    /**
     * @param $value
     */
    public function writeFloat($value)
    {
        $this->floatingPointWriter->write($this, $value);
    }

    /**
     * @param $string
     */
    public function writeULEB128($string)
    {
        $this->byteWriter->write($this, 11);
        $length = strlen($string);
        if($length == 0)
        {
            $this->byteWriter->write($this, 0);
        } else {
            $str = '';
            do{
                $char = $length & 0x7f;
                $length >>= 7;
                if($length > 0){
                    $char |= 0x80;
                }
                $str .= chr($char);
            }while($length);
            $this->stringWriter->write($this, $str);
            $this->stringWriter->write($this, $string);
        }
    }

    /**
     * @param  string               $endian
     * @return $this
     * @throws InvalidDataException
     */
    public function setEndian($endian)
    {
        if ($endian == 'big' || $endian == Endian::ENDIAN_BIG) {
            $this->endian = Endian::ENDIAN_BIG;
        } elseif ($endian == 'little' || $endian == Endian::ENDIAN_LITTLE) {
            $this->endian = Endian::ENDIAN_LITTLE;
        } else {
            throw new InvalidDataException('Endian must be set as big or little');
        }
        return $this;
    }
    /**
     * @return Type\Bit
     */
    public function getBitWriter()
    {
        return $this->bitWriter;
    }
    /**
     * @return Type\Byte
     */
    public function getByteWriter()
    {
        return $this->byteWriter;
    }
    /**
     * @return Type\Int8
     */
    public function getInt8Writer()
    {
        return $this->int8Writer;
    }
    /**
     * @return Type\Int16
     */
    public function getInt16Writer()
    {
        return $this->int16Writer;
    }
    /**
     * @return Type\Int32
     */
    public function getInt32Writer()
    {
        return $this->int32Writer;
    }

    /**
     * @return Type\Int64
     */
    public function getInt64Writer()
    {
        return $this->int64Writer;
    }
    /**
     * @return Type\String
     */
    public function getStringWriter()
    {
        return $this->stringWriter;
    }

    public function getFloatingPointWriter()
    {
        return $this->floatingPointWriter;
    }
}