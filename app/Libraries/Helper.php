<?php

namespace App\Libraries;

use Log;

/**
 * Class Helper
 * @package App\Libraries
 */
class Helper {
    /**
     * @param $string
     * @return array
     */
    public function uencode($string){
        $length = strlen($string);
        if($length == 0){
            return array(11, 0);
        }
        $str = '';
        do{
            $char = $length & 0x7f;
            $length >>= 7;
            if($length > 0){
                $char |= 0x80;
            }
            $str .= chr($char);
        }while($length);
        $str .= $string;
        return array_merge(array(11), unpack('C*', $str));
    }

    /**
     * @param $str
     * @param $x
     * @param int $maxlen
     * @return int
     */
    public function udecode($str, &$x, $maxlen = 16){
        $len = 0;
        $x = 0;
        while($str){
            $char = substr($str, 0, 1);
            $char = ord($char);
            $str = substr($str, 1);

            $x |= ($char & 0x7f) << (7 * $len);
            $len++;

            #Bin::debugInt($char);

            if(($char & 0x80) == 0){
                break;
            }

            if($len >= $maxlen) throw new \RuntimeException('String is greater than max length');
        }
        return $len;
    }

    /**
     * @param $long
     * @return array
     */
    public function GetLongBytes($long) {
        $value = $long;
        $highMap = 0xffffffff00000000;
        $lowMap = 0x00000000ffffffff;
        $higher = ($value & $highMap) >>32;
        $lower = $value & $lowMap;
        $packed = pack('NN', $higher, $lower);
        return array_reverse(unpack('C*', $packed));
    }

    /**
     * @return string
     */
    public function generateToken() {
        $randomString = sprintf("%s-%s-%s-%s-%s",str_random(8),str_random(4),str_random(4),str_random(4),str_random(12));
        return $randomString;
    }

    /**
     * @param $data
     * @return array
     */
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

    /**
     * @param $replayId
     * @param $name
     * @param $score
     * @param $combo
     * @param $count50
     * @param $count100
     * @param $count300
     * @param $countMiss
     * @param $countKatu
     * @param $countGeki
     * @param $FC
     * @param $mods
     * @param $avatarID
     * @param $rank
     * @param $timestamp
     * @return string
     */
    public function scoreString($replayId, $name, $score, $combo, $count50, $count100, $count300, $countMiss, $countKatu, $countGeki, $FC, $mods, $avatarID, $rank, $timestamp)
    {
        return sprintf("%d|%s|%d|%d|%d|%d|%d|%d|%d|%d|%d|%d|%d|%d|%d|1\n",$replayId, $name, $score, $combo, $count50, $count100, $count300, $countMiss, $countKatu, $countGeki, $FC, $mods, $avatarID, $rank, $timestamp);
    }

    /**
     * @param $text
     * @param $iv
     * @param null $version
     * @return string
     */
    public function decrypt($text, $iv, $version = null)
    {
        $text = base64_decode($text);
        $iv = base64_decode($iv);
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, is_null($version) ? config('bancho.decryptionKey') : sprintf("osu!-scoreburgr---------%s", $version), $text, MCRYPT_MODE_CBC, $iv);
    }

    /**
     * @param bool $asString
     * @return float|string
     */
    public function getTimestamp($asString=false){
        $seconds = microtime(true); // false = int, true = float
        $stamp = round($seconds * 10000);
        if($asString == true){
            return sprintf('%.0f', $stamp);
        } else {
            return $stamp;
        }
    }
}