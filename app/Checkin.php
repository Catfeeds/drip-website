<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checkin extends Model
{
    use SoftDeletes;

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    // 获取对应的动态
    public function event()
    {
        return $this->hasOne('App\Models\Event');
    }

    public function items()
    {
        return $this->belongsToMany('App\Item','checkin_item','checkin_id','item_id')->withPivot('item_value');
    }

    // 获取附件
    public function attaches()
    {
        return $this->morphMany('App\Models\Attach', 'attachable');
    }
}
