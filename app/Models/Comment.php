<?php
/**
 * 评论模型.
 * User: tuo3
 * Date: 16/7/15
 * Time: 下午2:47
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'event_comment';
    protected $primaryKey = 'comment_id';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    // 获取点赞记录
    public function likes()
    {
        return $this->hasMany('App\Like');
    }
}