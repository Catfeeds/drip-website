<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', 'HomeController@index');

Route::get('goal', 'GoalController@explore');

Route::get('account/login', 'AccountController@login');
Route::get('account/forget', 'AccountController@forget');
Route::get('account/reset', 'AccountController@reset');


Route::get('goal/view/{id}','GoalController@view');

Route::group(['namespace' => 'Admin','middleware' => ['web']], function () {
//	Route::controller('user', 'UserController', [
//		'ajax_users'  => 'user.ajax_users',
//		'index' => 'user',
//	]);
	Route::get('admin/auth/login', 'AuthController@getLogin');
	Route::post('admin/auth/login', 'AuthController@postLogin');
	Route::get('admin/user/users', 'UserController@users');
	Route::get('admin/user/user_view/{id}', 'UserController@user_view');
	Route::get('admin/user/ajax_users', 'UserController@ajax_users')->name('admin.user.ajax_users');
	Route::get('admin/user/ajax_user_goals', 'UserController@ajax_user_goals')->name('admin.user.ajax_user_goals');
	Route::get('admin/user/feedbacks', 'UserController@feedbacks');
	Route::get('admin/user/ajax_feedbacks', 'UserController@ajax_feedbacks')->name('admin.user.ajax_feedbacks');
	Route::post('admin/user/deal_feedback', 'UserController@deal_feedback')->name('admin.user.deal_feedback');
	Route::get('admin/user/events', 'UserController@events');
	Route::get('admin/user/ajax_events', 'UserController@ajax_events')->name('admin.user.ajax_events');
	Route::post('admin/user/hot_event', 'UserController@hot_event')->name('admin.user.hot_event');
	Route::get('admin/version', 'VersionController@index');
	Route::get('admin/version/ajax_versions', 'VersionController@ajax_versions')->name('admin.version.ajax_versions');
	Route::post('admin/version/create', 'VersionController@create')->name('admin.version.create');
	Route::get('admin/mall', 'MallController@index');
	Route::get('admin/mall/ajax_feedbacks', 'MallController@ajax_goods')->name('admin.mall.ajax_goods');

});

$api = app('Dingo\Api\Routing\Router');

// ['middleware' => 'jwt.api.auth']
$api->version('v1',['middleware'=>'cors'],function ($api) {
	$api->post('auth/login', 'App\Http\Controllers\Api\V1\AuthController@login');
	$api->post('auth/register', 'App\Http\Controllers\Api\V1\AuthController@register');
	$api->post('auth/oauth', 'App\Http\Controllers\Api\V1\AuthController@oauth');
	$api->post('auth/bind', 'App\Http\Controllers\Api\V1\AuthController@bind');
	$api->post('auth/get_verify_code', 'App\Http\Controllers\Api\V1\AuthController@get_verify_code');
	$api->post('auth/find', 'App\Http\Controllers\Api\V1\AuthController@find');
});

$api->version('v2',['middleware'=>'cors'],function ($api) {
	$api->post('auth/login', 'App\Http\Controllers\Api\V2\AuthController@login');
//	$api->post('auth/register', 'App\Http\Controllers\Api\V2\AuthController@register');
//	$api->post('auth/oauth', 'App\Http\Controllers\Api\V2\AuthController@oauth');
//	$api->post('auth/bind', 'App\Http\Controllers\Api\V2\AuthController@bind');
//	$api->post('auth/get_verify_code', 'App\Http\Controllers\Api\V2\AuthController@get_verify_code');
//	$api->post('auth/find', 'App\Http\Controllers\Api\V2\AuthController@find');
});

$api->version('v2',['middleware' => ['cors','jwt.auth']],function ($api) {
	$api->get('user/goals', 'App\Http\Controllers\Api\V2\UserController@goals');
});

$api->version('v1',['middleware' => ['cors','jwt.auth']],function ($api) {

	$api->get('event/all', 'App\Http\Controllers\Api\V1\EventController@all');
	$api->get('user/info', 'App\Http\Controllers\Api\V1\UserController@info');
	$api->get('user/goal', 'App\Http\Controllers\Api\V1\UserController@goal');
	$api->get('user/goals', 'App\Http\Controllers\Api\V1\UserController@goals');
	$api->get('user/events', 'App\Http\Controllers\Api\V1\UserController@events');
	$api->get('user/messages', 'App\Http\Controllers\Api\V1\UserController@messages');
	$api->post('user/feedback', 'App\Http\Controllers\Api\V1\UserController@feedback');
	$api->get('user/new_messages', 'App\Http\Controllers\Api\V1\UserController@new_messages');
	$api->post('user/profile', 'App\Http\Controllers\Api\V1\UserController@profile');
	$api->post('user/report', 'App\Http\Controllers\Api\V1\UserController@report');
	$api->post('user/follow', 'App\Http\Controllers\Api\V1\UserController@follow');
	$api->post('user/unfollow', 'App\Http\Controllers\Api\V1\UserController@unfollow');
	$api->get('user/fans', 'App\Http\Controllers\Api\V1\UserController@fans');
	$api->get('user/follow', 'App\Http\Controllers\Api\V1\UserController@follows');
	$api->get('user/energy', 'App\Http\Controllers\Api\V1\UserController@energy');
	$api->get('user/level', 'App\Http\Controllers\Api\V1\UserController@level');
	$api->get('message/like', 'App\Http\Controllers\Api\V1\MessageController@like');
	$api->get('message/comment', 'App\Http\Controllers\Api\V1\MessageController@comment');
	$api->get('message/notice', 'App\Http\Controllers\Api\V1\MessageController@notice');
	$api->get('message/fan', 'App\Http\Controllers\Api\V1\MessageController@fan');
	$api->get('top/users', 'App\Http\Controllers\Api\V1\TopController@users');
	$api->get('goal/all', 'App\Http\Controllers\Api\V1\GoalController@all');
	$api->get('goal/top', 'App\Http\Controllers\Api\V1\GoalController@top');
	$api->get('goal/info', 'App\Http\Controllers\Api\V1\GoalController@info');
	$api->post('goal/reorder', 'App\Http\Controllers\Api\V1\GoalController@reorder');
	$api->post('goal/follow', 'App\Http\Controllers\Api\V1\GoalController@follow');
	$api->post('goal/create', 'App\Http\Controllers\Api\V1\GoalController@create');
	$api->post('goal/update', 'App\Http\Controllers\Api\V1\GoalController@update');
	$api->post('goal/delete', 'App\Http\Controllers\Api\V1\GoalController@delete');
	$api->post('goal/remind', 'App\Http\Controllers\Api\V1\GoalController@remind');
	$api->post('goal/setting', 'App\Http\Controllers\Api\V1\GoalController@setting');
	$api->get('goal/week', 'App\Http\Controllers\Api\V1\GoalController@week');
	$api->get('goal/month', 'App\Http\Controllers\Api\V1\GoalController@month');
	$api->get('goal/year', 'App\Http\Controllers\Api\V1\GoalController@year');
	$api->get('goal/items', 'App\Http\Controllers\Api\V1\GoalController@items');
	$api->get('goal/checkin', 'App\Http\Controllers\Api\V1\GoalController@checkin');
	$api->get('goal/checkins', 'App\Http\Controllers\Api\V1\GoalController@checkins');
	$api->get('goal/events', 'App\Http\Controllers\Api\V1\GoalController@events');
	$api->post('checkin/create', 'App\Http\Controllers\Api\V1\CheckinController@create');
	$api->get('event/info', 'App\Http\Controllers\Api\V1\EventController@info');
	$api->post('event/like', 'App\Http\Controllers\Api\V1\EventController@like');
	$api->get('event/likes', 'App\Http\Controllers\Api\V1\EventController@likes');
	$api->post('event/comment', 'App\Http\Controllers\Api\V1\EventController@comment');
	$api->post('upload/image', 'App\Http\Controllers\Api\V1\UploadController@image');
	$api->post('update/check', 'App\Http\Controllers\Api\V1\UpdateController@check');
	$api->get('topic/info', 'App\Http\Controllers\Api\V1\TopicController@info');
	$api->get('topic/events', 'App\Http\Controllers\Api\V1\TopicController@events');
	$api->post('comment/like', 'App\Http\Controllers\Api\V1\CommentController@like');
	$api->get('good/info', 'App\Http\Controllers\Api\V1\GoodController@info');
	$api->get('good/hot', 'App\Http\Controllers\Api\V1\GoodController@hot');

});


/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    //
});
