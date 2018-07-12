<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/5/31
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserGoalItem extends Model
{
    use SoftDeletes;


    protected $table = 'user_goal_item';


    protected $fillable = [

    ];

    /**
     * 获取用户对象
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function goal()
    {
        return $this->belongsTo('App\Goal');
    }
}