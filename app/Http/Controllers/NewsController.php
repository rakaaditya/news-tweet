<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller as Base;
use App\Http\Controllers\TwitterController as Twitter;
use Illuminate\Http\Request;

class NewsController extends Base
{


    public function byCategory(Request $request, $category)
    {
        $screenName     = [
            'sport' => [
                // 'detiksport',
                'KompasBola',
                // 'Lip6Sport'
            ],
            'tech' => [
                'detikinet',
                // 'KompasTekno',
                'Lip6Tekno'
            ]
        ];

        $articles = null;
        
        foreach($screenName[$category] as $key => $val) {
           $articles[] = Twitter::screenName($val)
                        ->count($request->input('count'))
                        ->sinceId($request->input('since_id'))
                        ->maxId($request->input('max_id'))
                        ->get();
        }
        $articles = call_user_func_array('array_merge',$articles);
        
        usort($articles, function($a, $b) {
            return $b['tweet_id'] - $a['tweet_id'];
        });

        $result = [
            'screen_name'   => implode(', ', $screenName[$category]),
            'count'         => $request->input('count') ? $request->input('count') : 15,
            'data'          => $articles
        ];

        return response()->json($result)
                 ->setCallback($request->input('callback'));
    }

    public function byScreenName(Request $request, $screenName)
    {   
        $articles = Twitter::screenName($screenName)
                        ->count($request->input('count'))
                        ->sinceId($request->input('since_id'))
                        ->maxId($request->input('max_id'))
                        ->get();

        $result = [
            'screen_name'   => $screenName,
            'count'         => $request->input('count') ? $request->input('count') : 15,
            'data'          => $articles
        ];

        return response()->json($result)
                 ->setCallback($request->input('callback'));
    }
}
