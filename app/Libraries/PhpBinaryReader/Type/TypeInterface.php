<?php

namespace App\Libraries\PhpBinaryReader\Type;

use App\Libraries\PhpBinaryReader\BinaryReader;

interface TypeInterface
{
    /**
     * @param \App\Libraries\PhpBinaryReader\BinaryReader $br
     * @param int|null                      $length
     */
    public function read(BinaryReader &$br, $length);
}
