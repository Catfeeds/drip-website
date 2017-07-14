<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $table = 'topic';
    public $timestamps = false;

    /**
     * 获取所有拥有的 imageable 模型。
     */
    public function events()
    {
        return $this->belongsToMany('App\Event','event_topic');
    }
}
