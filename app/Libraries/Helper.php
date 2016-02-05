<?php

namespace App\Libraries;

class Helper {
    public function ULeb128($string) {
        if ($string == '') return array(0);
        $toreturn = array();
        $toreturn = array_merge(
            array(11, strlen($string)),
            unpack('C*', $string));
        //var_dump($toreturn);
        return $toreturn;
    }

    public function GetLongBytes($long) {
        $value = $long;
        $highMap = 0xffffffff00000000;
        $lowMap = 0x00000000ffffffff;
        $higher = ($value & $highMap) >>32;
        $lower = $value & $lowMap;
        $packed = pack('NN', $higher, $lower);
        return array_reverse(unpack('C*', $packed));
    }

    public function generateToken() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 8; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $randomString .= "-";
        for ($i = 0; $i < 4; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $randomString .= "-";
        for ($i = 0; $i < 4; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $randomString .= "-";
        for ($i = 0; $i < 4; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $randomString .= "-";
        for ($i = 0; $i < 12; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}