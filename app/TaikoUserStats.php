<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaikoUserStats extends Model
{
    protected $table = 'taiko_user_stats';
    protected $guarded = array();

    public function User()
    {
        return $this->belongsTo('App\User');
    }
}
