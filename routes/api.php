<?php

use App\Services\CardFinder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

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

Route::post('/test/message', 'TestController@message');
Route::get('/test/empty', 'TestController@returnEmpty');
Route::post('/slack', 'SlackController@handleEvent');

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
