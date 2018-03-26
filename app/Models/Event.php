<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'event_id';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'is_public'
        ];

    public function checkin()
    {
        return $this->hasOne('App\Checkin','id','event_value');
    }

    public function eventable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function goal()
    {
        return $this->belongsTo('App\Goal');
    }

    public function items()
    {
        return $this->hasMany('App\Item','item_id','event_value');
        // return $this->hasManyThrough('App\Checkin','App\Item','item_id','goal_id');
    }

    // 获取点赞记录
    public function likes()
    {
        return $this->hasMany('App\Like');
    }

    // 获取评论记录
    public function comments()
    {
        return $this->hasMany('App\Models\Comment');
    }
}
