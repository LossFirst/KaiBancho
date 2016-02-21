<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserBan extends Model
{
    protected $table = 'ban_list';
    protected $guarded = array();
}
