<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFollow extends Model
{
//    protected $table = 'topic';
    protected $primaryKey = 'user_id';

    public function events()
    {
        return $this->hasMany('App\Models\Event','user_id','follow_user_id');
    }
}
