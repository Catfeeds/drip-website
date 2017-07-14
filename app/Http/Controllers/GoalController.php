<?php namespace App\Http\Controllers;

use App\Goal;
use App\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GoalController extends Controller {

    /**
     * 显示所给定的用户个人数据。
     *
     * @param  int  $id
     * @return Response
     */
    public function index()
    {
        return view('home');
    }

    public function explore()
    {
        $goals = Goal::where([])->orderBy('create_time','desc')->take(20)->get();;

       return view('goal.explore', ['goals' => $goals]);
    }

    public function view(Request $request)
    {
        $goal_id = $request->route('id');

        $goal = Goal::findOrFail($goal_id);

        $events = Goal::find($goal_id)->events->take(10);

        return view('goal.view',['goal' => $goal,'events'=>$events]);
    }

}