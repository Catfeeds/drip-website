<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/2/24
 */

namespace  App\Http\Controllers\Api\V2\Transformers;

use League\Fractal\TransformerAbstract;
use App\User;

use DB;
use Carbon\Carbon;

class GoalTransformer extends TransformerAbstract
{
    public function transform(Goal $goal)
    {

    }
}