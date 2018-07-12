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
Route::get('/image', 'HomeController@image');

Route::get('blog/', 'ArticleController@index');

Route::get('goal', 'GoalController@explore');

Route::get('account/login', 'AccountController@login');
Route::get('account/forget', 'AccountController@forget');
Route::get('account/reset', 'AccountController@reset');
Route::get('goal/view/{id}','GoalController@view');
Route::get('article/{id}','ArticleController@view');
Route::post('wechat/notify', 'WechatController@notify');

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
    Route::post('admin/user/delete_feedback', 'UserController@delete_feedback')->name('admin.user.delete_feedback');
    Route::post('admin/user/add_vip', 'UserController@add_vip')->name('admin.user.add_vip');
    Route::post('admin/user/add_coin', 'UserController@add_coin')->name('admin.user.add_coin');
    Route::get('admin/user/events', 'UserController@events');
	Route::get('admin/user/ajax_events', 'UserController@ajax_events')->name('admin.user.ajax_events');
    Route::get('admin/user/test', 'UserController@test');
    Route::post('admin/version/delete', 'VersionController@delete')->name('admin.version.delete');

    Route::post('admin/user/hot_event', 'UserController@hot_event')->name('admin.user.hot_event');

    Route::get('admin/app/versions', 'VersionController@index');
	Route::get('admin/app/get_versions', 'VersionController@get_versions')->name('admin.app.get_versions');
	Route::post('admin/app/create_version', 'VersionController@create')->name('admin.app.create_version');

    Route::get('admin/app/channels', 'ChannelController@index');
    Route::get('admin/app/get_channels', 'ChannelController@get_channels')->name('admin.app.get_channels');
    Route::post('admin/app/create_channel', 'VersionController@create')->name('admin.app.create_channel');


    Route::get('admin/mall', 'MallController@index');
    Route::get('admin/blog/articles', 'ArticleController@index');
    Route::get('admin/blog/article/create', 'ArticleController@create');
    Route::get('admin/blog/article/lists', 'ArticleController@lists')->name('admin.blog.article.lists');
    Route::post('admin/blog/article/store', 'ArticleController@store')->name('admin.blog.article.store');
    Route::get('admin/mall/ajax_feedbacks', 'MallController@ajax_goods')->name('admin.mall.ajax_goods');
});

Route::group(['namespace' => 'Mobile','middleware' => ['web']], function () {
    Route::get('event/{id}', 'EventController@getEventDetail');
});


$api = app('Dingo\Api\Routing\Router');

// ['middleware' => 'jwt.api.auth']
// $api->version('v1',['middleware'=>'cors'],function ($api) {
// 	$api->post('auth/login', 'App\Http\Controllers\Api\V1\AuthController@login');
// 	$api->post('auth/register', 'App\Http\Controllers\Api\V1\AuthController@register');
// 	$api->post('auth/oauth', 'App\Http\Controllers\Api\V1\AuthController@oauth');
// 	$api->post('auth/bind', 'App\Http\Controllers\Api\V1\AuthController@bind');
// 	$api->post('auth/get_verify_code', 'App\Http\Controllers\Api\V1\AuthController@get_verify_code');
// 	$api->post('auth/find', 'App\Http\Controllers\Api\V1\AuthController@find');
// });

$api->version('v2',['middleware'=>'cors'],function ($api) {
	$api->post('auth/login', 'App\Http\Controllers\Api\V2\AuthController@login');
	$api->post('auth/register', 'App\Http\Controllers\Api\V2\AuthController@register');
	$api->post('auth/code', 'App\Http\Controllers\Api\V2\AuthController@getCode');
	$api->post('auth/find', 'App\Http\Controllers\Api\V2\AuthController@find');
	$api->post('auth/third', 'App\Http\Controllers\Api\V2\AuthController@thirdLogin');
//    $api->post('wechat/notify', 'App\Http\Controllers\Api\V2\WechatController@notify');

//	$api->post('auth/oauth', 'App\Http\Controllers\Api\V2\AuthController@oauth');
//	$api->post('auth/bind', 'App\Http\Controllers\Api\V2\AuthController@bind');
//	$api->post('auth/get_verify_code', 'App\Http\Controllers\Api\V2\AuthController@get_verify_code');
//	$api->post('auth/find', 'App\Http\Controllers\Api\V2\AuthController@find');
});

$api->version('v2',['middleware' => ['cors','jwt.auth']],function ($api) {
    $api->post('user/password/change', 'App\Http\Controllers\Api\V2\UserController@changePassword');
    $api->get('user/goals', 'App\Http\Controllers\Api\V2\UserController@getGoals');
    $api->get('user/info', 'App\Http\Controllers\Api\V2\UserController@getUserInfo');
    $api->get('user/{id}/goals', 'App\Http\Controllers\Api\V2\GoalController@getUserGoals');
    $api->get('user/{id}/photos', 'App\Http\Controllers\Api\V2\UserController@getPhotos');
    $api->get('user/goals/calendar', 'App\Http\Controllers\Api\V2\UserController@getGoalsCalendar');
    $api->get('message/{id}', 'App\Http\Controllers\Api\V2\UserController@getMessageDetail');
    $api->get('user/messages/fan', 'App\Http\Controllers\Api\V2\UserController@getFanMessages');
	$api->get('user/messages/comment', 'App\Http\Controllers\Api\V2\UserController@getCommentMessages');
	$api->get('user/messages/like', 'App\Http\Controllers\Api\V2\UserController@getLikeMessages');
	$api->get('user/messages/new', 'App\Http\Controllers\Api\V2\UserController@getNewMessages');
    $api->get('user/messages/notice', 'App\Http\Controllers\Api\V2\UserController@getNoticeMessages');
    $api->post('user/feedback', 'App\Http\Controllers\Api\V2\UserController@feedback');
    $api->get('user/coin/logs', 'App\Http\Controllers\Api\V2\UserController@getCoinLog');
    $api->get('user/{id}', 'App\Http\Controllers\Api\V2\UserController@getUser')->where('id', '[0-9]+');
    $api->get('user/search', 'App\Http\Controllers\Api\V2\UserController@getSearchUsers');
    $api->patch('user/{id}', 'App\Http\Controllers\Api\V2\UserController@updateUser');
    $api->get('user/{id}/fans', 'App\Http\Controllers\Api\V2\UserController@getFans');
    $api->get('user/{id}/followers', 'App\Http\Controllers\Api\V2\UserController@getFollowers');
    $api->get('user/{id}/followings', 'App\Http\Controllers\Api\V2\UserController@getFollowings');
    $api->get('user/{id}/events', 'App\Http\Controllers\Api\V2\UserController@getUserEvents');
	$api->get('user/goal/{id}', 'App\Http\Controllers\Api\V2\UserController@getGoal');
	$api->get('user/goal/{id}/events', 'App\Http\Controllers\Api\V2\UserController@getGoalEvents');
	$api->get('user/goal/{id}/chart', 'App\Http\Controllers\Api\V2\UserController@getGoalChart');
	$api->get('user/goal/{id}/day', 'App\Http\Controllers\Api\V2\UserController@getGoalDay');
	$api->get('user/goal/{id}/week', 'App\Http\Controllers\Api\V2\UserController@getGoalWeek');
	$api->get('user/goal/{id}/calendar', 'App\Http\Controllers\Api\V2\UserController@getGoalCalendar');
	$api->post('user/goal/{id}/checkin', 'App\Http\Controllers\Api\V2\GoalController@checkin');
	$api->patch('user/goal/{id}', 'App\Http\Controllers\Api\V2\UserController@updateGoal');
	$api->delete('user/goal/{id}', 'App\Http\Controllers\Api\V2\GoalController@delete');
	$api->post('goal/create', 'App\Http\Controllers\Api\V2\GoalController@create');
    $api->get('goal/search', 'App\Http\Controllers\Api\V2\GoalController@search');
    $api->get('goal/{id}', 'App\Http\Controllers\Api\V2\GoalController@getGoalDetail');
    $api->get('goal/{id}/events', 'App\Http\Controllers\Api\V2\GoalController@getGoalEvents');
    $api->get('goal/{id}/member', 'App\Http\Controllers\Api\V2\GoalController@getGoalMembers');
    $api->get('goal/{id}/top', 'App\Http\Controllers\Api\V2\GoalController@getGoalTop');
    $api->put('goal/{id}/follow', 'App\Http\Controllers\Api\V2\GoalController@doFollow');
    $api->patch('goal/{id}/audit', 'App\Http\Controllers\Api\V2\GoalController@doAudit');
    $api->put('user/follow/{id}', 'App\Http\Controllers\Api\V2\UserController@follow');
	$api->delete('user/follow/{id}', 'App\Http\Controllers\Api\V2\UserController@unFollow');
	$api->put('event/{id}/like', 'App\Http\Controllers\Api\V2\EventController@like');
	$api->delete('event/{id}/like', 'App\Http\Controllers\Api\V2\EventController@unLike');
    $api->post('event/{id}/comment', 'App\Http\Controllers\Api\V2\EventController@comment');
    $api->get('event/hot', 'App\Http\Controllers\Api\V2\EventController@getHotEvents');
	$api->get('event/follow', 'App\Http\Controllers\Api\V2\EventController@getFollowEvents');
	$api->get('event/{id}', 'App\Http\Controllers\Api\V2\EventController@getEventDetail');
    $api->patch('event/{id}', 'App\Http\Controllers\Api\V2\EventController@updateEvent');
    $api->delete('event/{id}', 'App\Http\Controllers\Api\V2\EventController@deleteEvent');
    $api->get('event/{id}/likes', 'App\Http\Controllers\Api\V2\EventController@getEventLikes');
	$api->post('comment/{id}/reply', 'App\Http\Controllers\Api\V2\CommentController@reply');
	$api->put('comment/{id}/like', 'App\Http\Controllers\Api\V2\CommentController@like');
    $api->delete('comment/{id}/like', 'App\Http\Controllers\Api\V2\CommentController@unLike');
    $api->post('user/bind/phone', 'App\Http\Controllers\Api\V2\UserController@bindPhone');
    $api->post('user/bind/email', 'App\Http\Controllers\Api\V2\UserController@bindEmail');
    $api->post('user/bind/wechat', 'App\Http\Controllers\Api\V2\UserController@bindWechat');
    $api->post('user/bind/weibo', 'App\Http\Controllers\Api\V2\UserController@bindWeibo');
    $api->post('user/bind/qq', 'App\Http\Controllers\Api\V2\UserController@bindQQ');
    $api->post('vip/buy', 'App\Http\Controllers\Api\V2\UserController@buyVip');
    $api->post('upload/image', 'App\Http\Controllers\Api\V2\UploadController@image');
    $api->get('top/week', 'App\Http\Controllers\Api\V2\TopController@week');
    $api->get('top/month', 'App\Http\Controllers\Api\V2\TopController@month');
    $api->get('top/year', 'App\Http\Controllers\Api\V2\TopController@year');
    $api->get('top/all', 'App\Http\Controllers\Api\V2\TopController@all');
    $api->get('topic/{name}', 'App\Http\Controllers\Api\V2\TopicController@getTopic');
    $api->get('topic/{name}/events', 'App\Http\Controllers\Api\V2\TopicController@getTopicEvents');
    $api->get('update/check', 'App\Http\Controllers\Api\V2\UpdateController@check');
    $api->get('mall/goods', 'App\Http\Controllers\Api\V2\MallController@getGoods');
    $api->get('mall/exchanges', 'App\Http\Controllers\Api\V2\MallController@getExchanges');
    $api->get('mall/good/{id}', 'App\Http\Controllers\Api\V2\MallController@getGoodDetail');
    $api->post('mall/good/{id}/exchange', 'App\Http\Controllers\Api\V2\MallController@doExchangeGood');
    $api->post('wechat/pay', 'App\Http\Controllers\Api\V2\WechatController@pay');
    $api->get('wechat/recharges', 'App\Http\Controllers\Api\V2\WechatController@recharges');
    $api->get('update/audit', 'App\Http\Controllers\Api\V2\UpdateController@audit');
});


$api->version('v3',['middleware'=>'cors'],function ($api) {
    $api->post('auth/login', 'App\Http\Controllers\Api\V3\AuthController@login');
    $api->post('auth/register', 'App\Http\Controllers\Api\V3\AuthController@register');
    $api->post('auth/code', 'App\Http\Controllers\Api\V3\AuthController@getCode');
    $api->post('auth/find', 'App\Http\Controllers\Api\V3\AuthController@find');
    $api->post('auth/third', 'App\Http\Controllers\Api\V3\AuthController@thirdLogin');
//    $api->post('wechat/notify', 'App\Http\Controllers\Api\V2\WechatController@notify');

//	$api->post('auth/oauth', 'App\Http\Controllers\Api\V2\AuthController@oauth');
//	$api->post('auth/bind', 'App\Http\Controllers\Api\V2\AuthController@bind');
//	$api->post('auth/get_verify_code', 'App\Http\Controllers\Api\V2\AuthController@get_verify_code');
//	$api->post('auth/find', 'App\Http\Controllers\Api\V2\AuthController@find');
});


$api->version('v3',['middleware' => ['cors','jwt.auth']],function ($api) {
    $api->post('user/password/change', 'App\Http\Controllers\Api\V3\UserController@changePassword');
    $api->get('user/goals', 'App\Http\Controllers\Api\V3\UserController@getGoals');
    $api->get('user/info', 'App\Http\Controllers\Api\V3\UserController@getUserInfo');
    $api->get('user/{id}/goals', 'App\Http\Controllers\Api\V3\GoalController@getUserGoals');
    $api->get('user/{id}/photos', 'App\Http\Controllers\Api\V3\UserController@getPhotos');
    $api->get('user/goals/calendar', 'App\Http\Controllers\Api\V3\UserController@getGoalsCalendar');
    $api->get('message/{id}', 'App\Http\Controllers\Api\V3\UserController@getMessageDetail');
    $api->get('user/messages/fan', 'App\Http\Controllers\Api\V3\UserController@getFanMessages');
    $api->get('user/messages/comment', 'App\Http\Controllers\Api\V3\UserController@getCommentMessages');
    $api->get('user/messages/like', 'App\Http\Controllers\Api\V3\UserController@getLikeMessages');
    $api->get('user/messages/new', 'App\Http\Controllers\Api\V3\UserController@getNewMessages');
    $api->get('user/messages/notice', 'App\Http\Controllers\Api\V3\UserController@getNoticeMessages');
    $api->post('user/feedback', 'App\Http\Controllers\Api\V3\UserController@feedback');
    $api->get('user/coin/logs', 'App\Http\Controllers\Api\V3\UserController@getCoinLog');
    $api->get('user/{id}', 'App\Http\Controllers\Api\V3\UserController@getUser')->where('id', '[0-9]+');
    $api->get('user/search', 'App\Http\Controllers\Api\V3\UserController@getSearchUsers');
    $api->patch('user/{id}', 'App\Http\Controllers\Api\V3\UserController@updateUser');
    $api->get('user/{id}/fans', 'App\Http\Controllers\Api\V3\UserController@getFans');
    $api->get('user/{id}/followers', 'App\Http\Controllers\Api\V3\UserController@getFollowers');
    $api->get('user/{id}/followings', 'App\Http\Controllers\Api\V3\UserController@getFollowings');
    $api->get('user/{id}/events', 'App\Http\Controllers\Api\V3\UserController@getUserEvents');
    $api->get('user/goal/{id}', 'App\Http\Controllers\Api\V3\UserController@getGoal');
    $api->get('user/goal/{id}/events', 'App\Http\Controllers\Api\V3\UserController@getGoalEvents');
    $api->get('user/goal/{id}/chart', 'App\Http\Controllers\Api\V3\UserController@getGoalChart');
    $api->get('user/goal/{id}/day', 'App\Http\Controllers\Api\V3\UserController@getGoalDay');
    $api->get('user/goal/{id}/days', 'App\Http\Controllers\Api\V3\UserController@getGoalDays');
    $api->get('user/goal/{id}/week', 'App\Http\Controllers\Api\V3\UserController@getGoalWeek');
    $api->get('user/goal/{id}/calendar', 'App\Http\Controllers\Api\V3\UserController@getGoalCalendar');
    $api->post('user/goal/{id}/checkin', 'App\Http\Controllers\Api\V3\GoalController@checkin');
    $api->patch('user/goal/{id}', 'App\Http\Controllers\Api\V3\UserController@updateGoal');
    $api->delete('user/goal/{id}', 'App\Http\Controllers\Api\V3\GoalController@delete');
    $api->post('goal/create', 'App\Http\Controllers\Api\V3\GoalController@create');
    $api->get('goal/search', 'App\Http\Controllers\Api\V3\GoalController@search');
    $api->get('goal/{id}', 'App\Http\Controllers\Api\V3\GoalController@getGoalDetail');
    $api->get('goal/{id}/events', 'App\Http\Controllers\Api\V3\GoalController@getGoalEvents');
    $api->get('goal/{id}/member', 'App\Http\Controllers\Api\V3\GoalController@getGoalMembers');
    $api->get('goal/{id}/top', 'App\Http\Controllers\Api\V3\GoalController@getGoalTop');
    $api->put('goal/{id}/follow', 'App\Http\Controllers\Api\V3\GoalController@doFollow');
    $api->patch('goal/{id}/audit', 'App\Http\Controllers\Api\V3\GoalController@doAudit');
    $api->put('user/follow/{id}', 'App\Http\Controllers\Api\V3\UserController@follow');
    $api->delete('user/follow/{id}', 'App\Http\Controllers\Api\V3\UserController@unFollow');
    $api->put('event/{id}/like', 'App\Http\Controllers\Api\V3\EventController@like');
    $api->delete('event/{id}/like', 'App\Http\Controllers\Api\V3\EventController@unLike');
    $api->post('event/{id}/comment', 'App\Http\Controllers\Api\V3\EventController@comment');
    $api->get('event/hot', 'App\Http\Controllers\Api\V3\EventController@getHotEvents');
    $api->get('event/follow', 'App\Http\Controllers\Api\V3\EventController@getFollowEvents');
    $api->get('event/{id}', 'App\Http\Controllers\Api\V3\EventController@getEventDetail');
    $api->patch('event/{id}', 'App\Http\Controllers\Api\V3\EventController@updateEvent');
    $api->delete('event/{id}', 'App\Http\Controllers\Api\V3\EventController@deleteEvent');
    $api->get('event/{id}/likes', 'App\Http\Controllers\Api\V3\EventController@getEventLikes');
    $api->post('comment/{id}/reply', 'App\Http\Controllers\Api\V3\CommentController@reply');
    $api->put('comment/{id}/like', 'App\Http\Controllers\Api\V3\CommentController@like');
    $api->delete('comment/{id}/like', 'App\Http\Controllers\Api\V3\CommentController@unLike');
    $api->post('user/bind/phone', 'App\Http\Controllers\Api\V3\UserController@bindPhone');
    $api->post('user/bind/email', 'App\Http\Controllers\Api\V3\UserController@bindEmail');
    $api->post('user/bind/wechat', 'App\Http\Controllers\Api\V3\UserController@bindWechat');
    $api->post('user/bind/weibo', 'App\Http\Controllers\Api\V3\UserController@bindWeibo');
    $api->post('user/bind/qq', 'App\Http\Controllers\Api\V3\UserController@bindQQ');
    $api->post('vip/buy', 'App\Http\Controllers\Api\V3\UserController@buyVip');
    $api->post('upload/image', 'App\Http\Controllers\Api\V3\UploadController@image');
    $api->get('top/week', 'App\Http\Controllers\Api\V3\TopController@week');
    $api->get('top/month', 'App\Http\Controllers\Api\V3\TopController@month');
    $api->get('top/year', 'App\Http\Controllers\Api\V3\TopController@year');
    $api->get('top/all', 'App\Http\Controllers\Api\V3\TopController@all');
    $api->get('topic/{name}', 'App\Http\Controllers\Api\V3\TopicController@getTopic');
    $api->get('topic/{name}/events', 'App\Http\Controllers\Api\V3\TopicController@getTopicEvents');
    $api->get('update/check', 'App\Http\Controllers\Api\V3\UpdateController@check');
    $api->get('mall/goods', 'App\Http\Controllers\Api\V3\MallController@getGoods');
    $api->get('mall/exchanges', 'App\Http\Controllers\Api\V3\MallController@getExchanges');
    $api->get('mall/good/{id}', 'App\Http\Controllers\Api\V3\MallController@getGoodDetail');
    $api->post('mall/good/{id}/exchange', 'App\Http\Controllers\Api\V3\MallController@doExchangeGood');
    $api->post('wechat/pay', 'App\Http\Controllers\Api\V3\WechatController@pay');
    $api->get('wechat/recharges', 'App\Http\Controllers\Api\V3\WechatController@recharges');
    $api->get('update/audit', 'App\Http\Controllers\Api\V3\UpdateController@audit');
    $api->get('article/{id}', 'App\Http\Controllers\Api\V3\ArticleController@getDetail')->where('id', '[0-9]+');
    $api->get('article/top', 'App\Http\Controllers\Api\V3\ArticleController@getTop');

});


// $api->version('v1',['middleware' => ['cors','jwt.auth']],function ($api) {

// 	$api->get('event/all', 'App\Http\Controllers\Api\V1\EventController@all');
// 	$api->get('user/info', 'App\Http\Controllers\Api\V1\UserController@info');
// 	$api->get('user/goal', 'App\Http\Controllers\Api\V1\UserController@goal');
// 	$api->get('user/goals', 'App\Http\Controllers\Api\V1\UserController@goals');
// 	$api->get('user/events', 'App\Http\Controllers\Api\V1\UserController@events');
// 	$api->get('user/messages', 'App\Http\Controllers\Api\V1\UserController@messages');
// 	$api->post('user/feedback', 'App\Http\Controllers\Api\V1\UserController@feedback');
// 	$api->get('user/new_messages', 'App\Http\Controllers\Api\V1\UserController@new_messages');
// 	$api->post('user/profile', 'App\Http\Controllers\Api\V1\UserController@profile');
// 	$api->post('user/report', 'App\Http\Controllers\Api\V1\UserController@report');
// 	$api->post('user/follow', 'App\Http\Controllers\Api\V1\UserController@follow');
// 	$api->post('user/unfollow', 'App\Http\Controllers\Api\V1\UserController@unfollow');
// 	$api->get('user/fans', 'App\Http\Controllers\Api\V1\UserController@fans');
// 	$api->get('user/follow', 'App\Http\Controllers\Api\V1\UserController@follows');
// 	$api->get('user/energy', 'App\Http\Controllers\Api\V1\UserController@energy');
// 	$api->get('user/level', 'App\Http\Controllers\Api\V1\UserController@level');
// 	$api->get('message/like', 'App\Http\Controllers\Api\V1\MessageController@like');
// 	$api->get('message/comment', 'App\Http\Controllers\Api\V1\MessageController@comment');
// 	$api->get('message/notice', 'App\Http\Controllers\Api\V1\MessageController@notice');
// 	$api->get('message/fan', 'App\Http\Controllers\Api\V1\MessageController@fan');
// 	$api->get('top/users', 'App\Http\Controllers\Api\V1\TopController@users');
// 	$api->get('goal/all', 'App\Http\Controllers\Api\V1\GoalController@all');
// 	$api->get('goal/top', 'App\Http\Controllers\Api\V1\GoalController@top');
// 	$api->get('goal/info', 'App\Http\Controllers\Api\V1\GoalController@info');
// 	$api->post('goal/reorder', 'App\Http\Controllers\Api\V1\GoalController@reorder');
// 	$api->post('goal/follow', 'App\Http\Controllers\Api\V1\GoalController@follow');
// 	$api->post('goal/create', 'App\Http\Controllers\Api\V1\GoalController@create');
// 	$api->post('goal/update', 'App\Http\Controllers\Api\V1\GoalController@update');
// 	$api->post('goal/delete', 'App\Http\Controllers\Api\V1\GoalController@delete');
// 	$api->post('goal/remind', 'App\Http\Controllers\Api\V1\GoalController@remind');
// 	$api->post('goal/setting', 'App\Http\Controllers\Api\V1\GoalController@setting');
// 	$api->get('goal/week', 'App\Http\Controllers\Api\V1\GoalController@week');
// 	$api->get('goal/month', 'App\Http\Controllers\Api\V1\GoalController@month');
// 	$api->get('goal/year', 'App\Http\Controllers\Api\V1\GoalController@year');
// 	$api->get('goal/items', 'App\Http\Controllers\Api\V1\GoalController@items');
// 	$api->get('goal/checkin', 'App\Http\Controllers\Api\V1\GoalController@checkin');
// 	$api->get('goal/checkins', 'App\Http\Controllers\Api\V1\GoalController@checkins');
// 	$api->get('goal/events', 'App\Http\Controllers\Api\V1\GoalController@events');
// 	$api->post('checkin/create', 'App\Http\Controllers\Api\V1\CheckinController@create');
// 	$api->get('event/info', 'App\Http\Controllers\Api\V1\EventController@info');
// 	$api->post('event/like', 'App\Http\Controllers\Api\V1\EventController@like');
// 	$api->get('event/likes', 'App\Http\Controllers\Api\V1\EventController@likes');
// 	$api->post('event/comment', 'App\Http\Controllers\Api\V1\EventController@comment');
// 	$api->post('upload/image', 'App\Http\Controllers\Api\V1\UploadController@image');
// 	$api->post('update/check', 'App\Http\Controllers\Api\V1\UpdateController@check');
// 	$api->get('topic/info', 'App\Http\Controllers\Api\V1\TopicController@info');
// 	$api->get('topic/events', 'App\Http\Controllers\Api\V1\TopicController@events');
// 	$api->post('comment/like', 'App\Http\Controllers\Api\V1\CommentController@like');
// 	$api->get('good/info', 'App\Http\Controllers\Api\V1\GoodController@info');
// 	$api->get('good/hot', 'App\Http\Controllers\Api\V1\GoodController@hot');

// });


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
