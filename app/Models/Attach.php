<?php
/**
 * Created by PhpStorm.
 * User: Jason.z
 * Date: 16/8/1
 * Time: 下午7:05
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attach extends Model
{
    public function attachable()
    {
        return $this->morphTo();
    }
}