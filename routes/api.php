<?php

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// used for scraping the images from wiki.dominionstrategry.com
Route::get('/images', function() {
    $cardImages = [];
    $client = app()->make(\GuzzleHttp\Client::class);

    foreach(config('cards') as $card) {
        $cardName = str_replace(' ', '_', $card);
        $response = $client->get("http://wiki.dominionstrategy.com/index.php/File:$cardName.jpg");
        $html = new DOMDocument;
        @$html->loadHTML($response->getBody()->getContents());
        $url = $html->getElementById('file')->getElementsByTagName('img')[0]->getAttribute('src');
        $fullUrl = 'http://wiki.dominionstrategy.com/index.php' . $url;
        $cardImages[$card] = $fullUrl;
    }

    dd($cardImages);
});

Route::post('/slack', function(\Illuminate\Http\Request $request){

    $payload = $request->json();

    if ($payload->get('type') === 'url_verification') {
        return $payload->get('challenge');
    } else {
        $event = $payload->get('event');
        $channel = $event['channel'];

        if(isset($event['bot_id']) && $event['bot_id'] == 'B012ZRKSFLP') {
            return;
        } else {

            $cards = [];
            $dominionUrl = 'http://wiki.dominionstrategy.com/index.php/';
            foreach(config('cards') as $card) {
                if(Str::contains($event['text'], $card)) {
                    $cardName = str_replace(' ', '_', $card);
                    $cardImage = "<$dominionUrl$card|Wiki>";
                    $cardUrl = config("card_images.$card");
                    $cardWiki = "<$cardUrl|Card>";

                    $cards[] = [
                        'card_name' => $card,
                        'card_image' => $cardImage,
                        'image' => $cardUrl,
                        'alt_text' => $card,
                        'card_wiki' => $cardWiki
                    ];
                }
            }

            if(!empty($cards)) {
                $headers = [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . env('BOT_USER_ACCESS_TOKEN'),
                ];

                $blocks = [];
                $blocks[] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => 'Here are the cards you mentioned:'
                    ]
                ];

                foreach($cards as $card) {
                    $blocks[] = [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $card['card_name'] . ': ' . $card['card_wiki'] . ', ' . $card['card_image'],
                        ],
                    ];
                }

                $formParams = [
                    'channel' => $channel,
                    'unfurl_media' => false,
                    'blocks' => $blocks
                ];

                $params = [
                    'headers' => $headers,
                    'json' => $formParams,
                ];

                $httpClient = app()->make(\GuzzleHttp\Client::class);
                $httpClient->post('https://slack.com/api/chat.postMessage', $params);
            }
        }
    }
});

Route::middleware('web')->get('/login/slack', function(){
    return Socialite::with('slack')
        ->scopes(['bot'])
        ->redirect();
});

Route::middleware('web')->get('/connect/slack', function(\GuzzleHttp\Client $httpClient){
    $response = $httpClient->post('https://slack.com/api/oauth.access', [
        'headers' => ['Accept' => 'application/json'],
        'form_params' => [
            'client_id' => env('SLACK_KEY'),
            'client_secret' => env('SLACK_SECRET'),
            'code' => $_GET['code'],
            'redirect_uri' => env('SLACK_REDIRECT_URI'),
        ]
    ]);
    $token = json_decode($response->getBody())->bot->bot_access_token;
	echo $token;
});
