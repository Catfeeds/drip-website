<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Checkin extends Model
{
    protected $table = 'checkin';
    protected $primaryKey = 'checkin_id';


    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function items()
    {
        return $this->belongsToMany('App\Item','checkin_item','checkin_id','item_id')->withPivot('item_value');
    }
}
