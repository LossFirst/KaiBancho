<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function OsuUserStats()
    {
        return $this->hasOne('App\OsuUserStats');
    }

    public function TaikoUserStats()
    {
        return $this->hasOne('App\TaikoUserStats');
    }

    public function ManiaUserStats()
    {
        return $this->hasOne('App\ManiaUserStats');
    }

    public function CTBUserStats()
    {
        return $this->hasOne('App\CTBUserStats');
    }
}
