<?php

namespace App\Libraries\PhpBinaryReader;

use App\Libraries\PhpBinaryReader\Exception\InvalidDataException;
use App\Libraries\PhpBinaryReader\Type\Bit;
use App\Libraries\PhpBinaryReader\Type\Byte;
use App\Libraries\PhpBinaryReader\Type\Int8;
use App\Libraries\PhpBinaryReader\Type\Int16;
use App\Libraries\PhpBinaryReader\Type\Int32;
use App\Libraries\PhpBinaryReader\Type\Int64;
use App\Libraries\PhpBinaryReader\Type\String2;

/**
 * Class BinaryReader
 * @package App\Libraries\PhpBinaryReader
 */
class BinaryReader
{
    /**
     * @var int
     */
    private $machineByteOrder = Endian::ENDIAN_LITTLE;

    /**
     * @var resource 
     */
    private $inputHandle;

    /**
     * @var int
     */
    private $currentBit;

    /**
     * @var mixed
     */
    private $nextByte;

    /**
     * @var int
     */
    private $position;

    /**
     * @var int
     */
    private $eofPosition;

    /**
     * @var string
     */
    private $endian;

    /**
     * @var Type\Byte
     */
    private $byteReader;

    /**
     * @var Type\Bit
     */
    private $bitReader;

    /**
     * @var Type\String2
     */
    private $stringReader;

    /**
     * @var Type\Int8
     */
    private $int8Reader;

    /**
     * @var Type\Int16
     */
    private $int16Reader;

    /**
     * @var Type\Int32
     */
    private $int32Reader;

    /**
     * @var Type\Int64
     */
    private $int64Reader;

    /**
     * @param  string|resource           $input
     * @param  int|string                $endian
     * @throws \InvalidArgumentException
     */
    public function __construct($input, $endian = Endian::ENDIAN_LITTLE)
    {
        if (!is_resource($input)) {
            $this->setInputString($input);
        } else {
            $this->setInputHandle($input);
        }
        
        $this->eofPosition = fstat($this->getInputHandle())['size'];

        $this->setEndian($endian);
        $this->setNextByte(false);
        $this->setCurrentBit(0);
        $this->setPosition(0);

        $this->bitReader = new Bit();
        $this->stringReader = new String2();
        $this->byteReader = new Byte();
        $this->int8Reader = new Int8();
        $this->int16Reader = new Int16();
        $this->int32Reader = new Int32();
        $this->int64Reader = new Int64();
    }

    /**
     * @return bool
     */
    public function isEof()
    {
        return $this->position >= $this->eofPosition;
    }

    /**
     * @param int $length
     * @return bool
     */
    public function canReadBytes($length = 0)
    {
        return $this->position + $length <= $this->eofPosition;
    }

    /**
     * @return void
     */
    public function align()
    {
        $this->setCurrentBit(0);
        $this->setNextByte(false);
    }

    /**
     * @param  int $count
     * @return int
     */
    public function readBits($count)
    {
        return $this->bitReader->readSigned($this, $count);
    }

    /**
     * @param  int $count
     * @return int
     */
    public function readUBits($count)
    {
        return $this->bitReader->read($this, $count);
    }

    /**
     * @param  int $count
     * @return int
     */
    public function readBytes($count)
    {
        return $this->byteReader->read($this, $count);
    }

    /**
     * @return int
     */
    public function readInt8()
    {
        return $this->int8Reader->readSigned($this);
    }

    /**
     * @return int
     */
    public function readUInt8()
    {
        return $this->int8Reader->read($this);
    }

    /**
     * @return int
     */
    public function readInt16()
    {
        return $this->int16Reader->readSigned($this);
    }

    /**
     * @return string
     */
    public function readUInt16()
    {
        return $this->int16Reader->read($this);
    }

    /**
     * @return int
     */
    public function readInt32()
    {
        return $this->int32Reader->readSigned($this);
    }

    /**
     * @return int
     */
    public function readUInt32()
    {
        return $this->int32Reader->read($this);
    }

    /**
     * @return array|int
     */
    public function readUInt64()
    {
        return $this->int64Reader->read($this);
    }

    /**
     * @param  int    $length
     * @return string
     */
    public function readString($length)
    {
        return $this->stringReader->read($this, $length);
    }

    /**
     * @param  int    $length
     * @return string
     */
    public function readAlignedString($length)
    {
        return $this->stringReader->readAligned($this, $length);
    }

    /**
     * @return string
     */
    public function readULEB128()
    {
        return $this->stringReader->readULEB128($this);
    }

    /**
     * @param  int   $machineByteOrder
     * @return $this
     */
    public function setMachineByteOrder($machineByteOrder)
    {
        $this->machineByteOrder = $machineByteOrder;

        return $this;
    }

    /**
     * @return int
     */
    public function getMachineByteOrder()
    {
        return $this->machineByteOrder;
    }

    /**
     * @param  resource $inputHandle
     * @return $this
     */
    public function setInputHandle($inputHandle)
    {
        $this->inputHandle = $inputHandle;

        return $this;
    }

    /**
     * @return resource
     */
    public function getInputHandle()
    {
        return $this->inputHandle;
    }

    /**
     * @param string $inputString
     * @return $this
     */
    public function setInputString($inputString)
    {
        $handle = fopen('php://memory', 'br+');
        fwrite($handle, $inputString);
        rewind($handle);
        $this->inputHandle = $handle;

        return $this;
    }

    /**
     * @return string
     */
    public function getInputString()
    {
        $handle = $this->getInputHandle();
        $str = stream_get_contents($handle);
        rewind($handle);

        return $str;
    }

    /**
     * @param  mixed $nextByte
     * @return $this
     */
    public function setNextByte($nextByte)
    {
        $this->nextByte = $nextByte;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNextByte()
    {
        return $this->nextByte;
    }

    /**
     * @param  int   $position
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = $position;
        fseek($this->getInputHandle(), $position);

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return int
     */
    public function getEofPosition()
    {
        return $this->eofPosition;
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
     * @return string
     */
    public function getEndian()
    {
        return $this->endian;
    }

    /**
     * @param  int   $currentBit
     * @return $this
     */
    public function setCurrentBit($currentBit)
    {
        $this->currentBit = $currentBit;

        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentBit()
    {
        return $this->currentBit;
    }

    /**
     * @return Type\Bit
     */
    public function getBitReader()
    {
        return $this->bitReader;
    }

    /**
     * @return Type\Byte
     */
    public function getByteReader()
    {
        return $this->byteReader;
    }

    /**
     * @return Type\Int8
     */
    public function getInt8Reader()
    {
        return $this->int8Reader;
    }

    /**
     * @return Type\Int16
     */
    public function getInt16Reader()
    {
        return $this->int16Reader;
    }

    /**
     * @return Type\Int32
     */
    public function getInt32Reader()
    {
        return $this->int32Reader;
    }

    /**
     * @return Type\Int64
     */
    public function getInt64Reader()
    {
        return $this->int64Reader;
    }

    /**
     * @return Type\String2
     */
    public function getStringReader()
    {
        return $this->stringReader;
    }

    /**
     * Read a length of characters from the input handle, updating the
     * internal position marker.
     *
     * @return string
     */
    public function readFromHandle($length)
    {
        $this->position += $length;
        return fread($this->inputHandle, $length);
    }
}
