<?php

namespace App\Libraries;

use Cache;
use Log;

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
            cache::put('currentLogin', $currentUsers);
            return $currentUsers;
        }
        return array();
    }
}