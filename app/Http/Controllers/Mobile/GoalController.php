<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/3/29
 */


namespace App\Http\Controllers;

use App\Goal;
use App\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GoalController extends Controller
{

    /**
     * 显示所给定的用户个人数据。
     *
     * @param  int $id
     * @return Response
     */
    public function index()
    {
        return view('home');
    }

    public function getGoalInfo($goal_id,Request $request)
    {
    }
}