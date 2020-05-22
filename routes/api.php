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
        \Log::info($payload->get('challenge'));
        return $payload->get('challenge');
    } else {
        $event = $payload->get('event');
        $channel = $event['channel'];
        \Log::info($event['text']);

        if(isset($event['bot_id']) && $event['bot_id'] == 'B012ZRKSFLP') {
            \Log::info('this is me reacting to my own message');
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
                $httpClient = app()->make(\GuzzleHttp\Client::class);
                $headers = [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . 'xoxb-3665960514-1109879133494-o0ujPU2mcsI6p49ISStYy5FP',
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
                        // 'accessory' => [
                        //     "type" => 'image',
                        //     "image_url" => $card['image'],
                        //     "alt_text" => $card['alt_text'],
                        // ],
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

                $response = $httpClient->post('https://slack.com/api/chat.postMessage', $params);

                \Log::info($response->getBody()->getContents());
            }


        }



    }

    // Bot logic will be placed here
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
	$bot_token = json_decode($response->getBody())->bot->bot_access_token;
    echo "Your Bot Token is: ". $bot_token. " place it inside your .env as SLACK_TOKEN";
});
