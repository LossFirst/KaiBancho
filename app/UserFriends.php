<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFriends extends Model
{
    protected $table = 'friends_list';
    protected $guarded = array();
}
