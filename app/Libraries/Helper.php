<?php

namespace App\Libraries;

class Helper {
    public function ULeb128($string) {
        if ($string == '') return array(0);
        $toreturn = array_merge(
            array(11, strlen($string)),
            unpack('C*', $string));
        return $toreturn;
    }

    public function GetLongBytes($long) {
        $value = $long;
        $highMap = 0xfffffff000000000;
        $lowMap = 0x00000000ffffffff;
        $higher = ($value & $highMap) >>32;
        $lower = $value & $lowMap;
        $packed = pack('NN', $higher, $lower);
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

    public function parsePacket85($data)
    {
        $output = array();
        foreach(array_slice($data, 9) as $item)
        {
            if($item != 0)
            {
                array_push($output, $item);
            }
        }
        return $output;
    }

    public function scoreString($replayId, $name, $score, $combo, $count50, $count100, $count300, $countMiss, $countKatu, $countGeki, $FC, $mods, $avatarID, $rank, $timestamp)
    {
        return sprintf("%d|%s|%d|%d|%d|%d|%d|%d|%d|%d|%d|%d|%d|%d|%d|1\n",$replayId, $name, $score, $combo, $count50, $count100, $count300, $countMiss, $countKatu, $countGeki, $FC, $mods, $avatarID, $rank, $timestamp);
    }

    public function decrypt($text, $iv)
    {
        $text = base64_decode($text);
        $iv = base64_decode($iv);
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, config('bancho.decryptionKey'), $text, MCRYPT_MODE_CBC, $iv);
    }
}