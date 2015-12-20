<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller as Base;
use App\Http\Controllers\TwitterController as Twitter;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class NewsController extends Base
{
    function __construct()
    {
        $this->twitter = new Twitter();
    }

    public function index(Request $request, $screen_name = 'liputan6dotcom')
    {
        $data = $this->twitter->client()->get('statuses/user_timeline.json', [
            'query' => [
                'screen_name'       => $screen_name,
                'count'             => $request->input('count') ? $request->input('count') : 5,
                'since_id'          => $request->input('since_id') ? $request->input('since_id') : null,
                'max_id'            => $request->input('max_id') ? $request->input('max_id') : null,
                'exclude_replies'   => true
            ]
        ])->getBody();

        $data = json_decode($data);

        $articles = [];

        foreach($data as $row) {
            $articles[] = $this->ogParse($row);
        }

        $result = [
            'screen_name'   => $screen_name,
            'count'         => $request->input('count') ? $request->input('count') : 5,
            'data'          => $articles
        ];

        return response()->json($result)
                 ->setCallback($request->input('callback'));
    }

    private function ogParse($data)
    {
        libxml_use_internal_errors(true);

        $url = $data->entities->urls[0]->expanded_url;

        // In case URL using short URL
        $url = $this->shortUrlParse($url);
        
        $doc                = new \DOMDocument();
        $doc->loadHTMLFile($url);
        $xpath              = new \DOMXpath($doc);

        $ogImageTag         = $xpath->query("*/meta[@property='og:image']")->item(0);
        $ogTitleTag         = $xpath->query("*/meta[@property='og:title']")->item(0);
        $ogDescriptionTag   = $xpath->query("*/meta[@property='og:description']")->item(0);
        
        $result = [
            'tweet_id'      => $data->id,
            'title'         => $ogTitleTag->attributes->getNamedItem('content')->nodeValue,
            'image'         => $ogImageTag->attributes->getNamedItem('content')->nodeValue,
            'description'   => $ogDescriptionTag->attributes->getNamedItem('content')->nodeValue,
            'retweet'       => $data->retweet_count,
            'favorite'      => $data->favorite_count,

        ];

        return $result;
    }

    protected function shortUrlParse($url)
    {
        $client = new Client(['base_uri' => $url]);

        $response = $client->get('/');

        if ($response->hasHeader('Location'))
            $url = $response->getHeader('Location');

        return $url;
    }
}
