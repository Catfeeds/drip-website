<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'message_id';
    public $timestamps = false;

    /**
     * 获取所有拥有的 imageable 模型。
     */
    public function like()
    {
        return $this->morphTo('');
    }
}
