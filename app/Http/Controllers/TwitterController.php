<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller as Base;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Cache;

class TwitterController extends Base
{
    const KEY = 'newstweet:news:';

    private static  $screenName = 'cnnindonesia',
                    $count = 15,
                    $sinceId = null,
                    $maxId = null,
                    $excludeReplies = true,
                    $includeRts = false;

    private static function client()
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

    public static function screenName($screenName)
    {
        if($screenName)
            self::$screenName = $screenName;

        return new self;
    }

    public static function count($count)
    {
        if($count)
            self::$count = $count;

        return new self;
    }

    public static function sinceId($sinceId)
    {
        if($sinceId)
            self::$sinceId = $sinceId;

        return new self;
    }

    public static function maxId($maxId)
    {
        if($maxId)
            self::$maxId = $maxId;

        return new self;
    }

    public static function get()
    {
        $query = [
            'screen_name'       => self::$screenName,
            'count'             => self::$count,
            'since_id'          => self::$sinceId,
            'max_id'            => self::$maxId,
            'exclude_replies'   => true,
            'include_rts'       => false
        ];

        $key = self::KEY.$query['screen_name'].':'.$query['count'].':'.$query['since_id'].':'.$query['max_id'];

        if(! $articles = Cache::get($key) ) {
            $data = self::client()->get('statuses/user_timeline.json', [
                    'query' => $query
            ])->getBody();

            $data = json_decode($data);
            echo '<pre>'; print_r($data); die('dafuq');
            $articles = [];

            foreach($data as $row)
                if($row->entities->urls[0]->expanded_url)
                    $articles[] = self::ogParse($row);

            Cache::put($key, $articles, 10);
        }

        return $articles;
    }

    private static function ogParse($data)
    {
        libxml_use_internal_errors(true);

        $url = $data->entities->urls[0]->expanded_url;

        // In case URL using short URL
        $url = self::shortUrlParse($url);

        $doc                = new \DOMDocument();
        $doc->loadHTMLFile($url);
        $xpath              = new \DOMXpath($doc);

        $ogImageTag         = $xpath->query("*/meta[@property='og:image']")->item(0);
        $ogTitleTag         = $xpath->query("*/meta[@property='og:title']")->item(0);
        $ogDescriptionTag   = $xpath->query("*/meta[@property='og:description']")->item(0);
        
        $result = [
            'tweet_id'      => $data->id,
            'screen_name'   => $data->user->screen_name,
            'title'         => $ogTitleTag->attributes->getNamedItem('content')->nodeValue,
            'image'         => $ogImageTag->attributes->getNamedItem('content')->nodeValue,
            'description'   => $ogDescriptionTag->attributes->getNamedItem('content')->nodeValue,
            'url'           => $url,
            'retweet'       => $data->retweet_count,
            'favorite'      => $data->favorite_count,

        ];

        return $result;
    }

    private static function shortUrlParse($url)
    {
        $client = new Client(['base_uri' => $url]);

        $response = $client->get('/');

        if ($response->hasHeader('Location'))
            $url = $response->getHeader('Location');

        return $url;
    }
}
