<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $table = 'likes';
    protected $primaryKey = 'like_id';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
