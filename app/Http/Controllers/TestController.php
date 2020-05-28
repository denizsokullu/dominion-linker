<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CardFinder;

class TestController extends Controller
{
  public function message(Request $request) {
    $payload = $request->json();
    $message = $payload->get('message');

    $cardFinder = app()->make(CardFinder::class);
    $cards = $cardFinder->findCardsIn($message);

    return response()->json($cards);
  }
}
