<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller as Base;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class TwitterController extends Base
{
    public function client()
    {
        $stack = HandlerStack::create();

        $oauth = new Oauth1([
            'consumer_key'    => env('TW_CONSUMER_KEY'),
            'consumer_secret' => env('TW_CONSUMER_SECRET'),
            'token'           => env('TW_ACCESS_TOKEN'),
            'token_secret'    => env('TW_TOKEN_SECRET'),
        ]);
        
        $stack->push($oauth);
        
        $twitterClient = new Client([
            'base_uri'  => 'https://api.twitter.com/1.1/',
            'handler'   => $stack,
            'auth'      => 'oauth'
        ]);

        return $twitterClient;
    }
}
