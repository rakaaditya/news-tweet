<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller as Base;
use App\Http\Controllers\TwitterController as Twitter;
use Illuminate\Http\Request;

class NewsController extends Base
{

    public function index(Request $request, $screenName)
    {   
        $articles = Twitter::screenName($screenName)
                        ->count($request->input('count'))
                        ->sinceId($request->input('since_id'))
                        ->maxId($request->input('max_id'))
                        ->get();

        $result = [
            'screen_name'   => $screenName,
            'count'         => $request->input('count') ? $request->input('count') : 5,
            'data'          => $articles
        ];

        return response()->json($result)
                 ->setCallback($request->input('callback'));
    }
}
