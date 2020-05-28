<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CardFinder;

class SlackController extends Controller
{
  public function handleEvent(Request $request) {
    $payload = $request->json();

    if ($payload->get('type') === 'url_verification') {
        return $payload->get('challenge');
    } else {
        $event = $payload->get('event');
        $channel = $event['channel'];

        if(isset($event['bot_id']) && $event['bot_id'] == 'B012ZRKSFLP') {
            return;
        } else {
            $cardFinder = app()->make(CardFinder::class);
            $cards = $cardFinder->findCardsIn($event['text']);
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
  }
}
