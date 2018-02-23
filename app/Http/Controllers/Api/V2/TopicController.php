<?php
/**
 * Created by PhpStorm.
 * User: tuo3
 * Date: 2016/11/17
 * Time: 下午10:27
 */

namespace App\Http\Controllers\Api\V2;

use App\Like;
use Auth;
use Validator;
use API;
use DB;

use App\Event;
use App\Checkin;
use App\User;
use App\Models\Message as Message;
use App\Models\Topic as Topic;
use App\Libs\MyJpush as MyJpush;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Api\V2\Transformers\EventTransformer;
use League\Fractal\Serializer\ArraySerializer;


class TopicController extends BaseController {

    public function getTopic($name,Request $request) {

        $topic = Topic::Where('name','=',$name)->first();

//        $ret = array();
//
//        $ret['id'] = $topic->id;
//        $ret['follow_count'] = $topic->follow_count;
//        $ret['name'] = $topic->name;

        return $this->response->array($topic->toArray());

    }

    public function getTopicEvents($topic_id,Request $request) {

        $topic = Topic::find($topic_id);
        $type = $request->input('type','all');

        $events = [];

        if($topic) {
            if($type == 'hot') {
                $events = $topic->events()
                    ->where('is_hot','=',1)
                    ->where('is_public','=',1)
                    ->orderBy('create_time','DESC')
                    ->take(20)->get();
            }  else {
                $events =
                    $topic->events()
                        ->where('is_public','=',1)
                        ->orderBy('create_time','DESC')
                        ->take(20)->get();
            }
        }

        return $this->response->collection($events, new EventTransformer(),[],function($resource, $fractal){
            $fractal->setSerializer(new ArraySerializer());
        });

    }
}