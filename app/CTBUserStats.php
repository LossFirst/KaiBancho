<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CTBUserStats extends Model
{
    protected $table = 'ctb_user_stats';
    protected $guarded = array();

    public function User()
    {
        return $this->belongsTo('App\User');
    }
}
