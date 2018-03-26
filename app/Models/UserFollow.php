<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFollow extends Model
{
    /**
     * 获取关注的用户对象
     */
    public function follow_user()
    {
        return $this->belongsTo('App\User','follow_user_id');
    }

    /**
     * 获取用户对象
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
