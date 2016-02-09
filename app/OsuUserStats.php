<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OsuUserStats extends Model
{
    protected $table = 'osu_user_stats';
    protected $guarded = array();

    public function User()
    {
        return $this->belongsTo('App\User');
    }
}
