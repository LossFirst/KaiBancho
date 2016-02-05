<?php

namespace App\Libraries;

use Cache;
use Log;
use App\Libraries\Packet as Packet;

class Player {
    public function getAll()
    {
        if(Cache::has('currentLogin')) {
            $currentUsers = Cache::get('currentLogin');
            foreach($currentUsers as $key => $token)
            {
                if(!cache::tags(['user'])->has($token))
                {
                    unset($currentUsers[$key]);
                }
            }
            return $currentUsers;
        }
        return array();
    }

    public function getAllDetailed()
    {
        $packet = new Packet();
        $output = array();
        if(Cache::has('currentLogin')) {
            $currentUsers = Cache::get('currentLogin');
            foreach($currentUsers as $token)
            {
                if(cache::tags(['user'])->has($token))
                {
                    $user = cache::tags(['user'])->get($token);
                    $output = array_merge($output,
                        $packet->create(83, array(
                            'id' => $user->id,
                            'playerName' => $user->name,
                            'utcOffset' => 0 + 24,
                            'country' => 1,
                            'playerRank' => 0,
                            'longitude' => 0,
                            'latitude' => 0,
                            'globalRank' => 0,
                        ))
                    );
                }
            }
        }
        return $output;
    }
}