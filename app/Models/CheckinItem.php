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

class CheckinItem extends Model
{

    protected $table = 'checkin_item';

    protected $timestamp = false;

    protected $fillable = [

    ];

    /**
     * 获取用户对象
     */
    public function check()
    {
        return $this->belongsTo('App\Checkin');
    }

}