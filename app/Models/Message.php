<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'message_id';


    public function like()
    {
        return $this->morphTo('');
    }
}
