<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ManiaUserStats extends Model
{
    protected $table = 'mania_user_stats';
    protected $guarded = array();

    public function User()
    {
        return $this->belongsTo('App\User');
    }
}
