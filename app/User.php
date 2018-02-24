<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $primaryKey = 'id';

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

    public function goals()
    {
        return $this->belongsToMany('App\Goal','user_goals')
                    ->withPivot('start_date','end_date','status','total_days', 'series_days','energy','expect_days','is_del','start_time','last_checkin_time','is_push','is_public','remind_time','order','name','desc');
    }


    public function checkins()
    {
        return $this->hasMany('App\Checkin');
    }

    public function events()
    {
        return $this->hasMany('App\Event');
    }

    // 获取用户关注列表
    public function follows()
    {
        return $this->hasMany('App\Models\UserFollow');
    }

    public function checkinsCount()
    {
      return $this->checkins()
        ->selectRaw('user_id, count(*) as count')
        ->groupBy('user_id')
        ->orderBy('count','DESC');
    }

    public function getAuthSalt()
    {
     return $this->salt;
    }

    public function getAuthPassword() {
        return $this->passwd;
    }
}
