<?php

namespace App\Services;

class CardFinder {

  const DOMINION_WIKI_URL = 'http://wiki.dominionstrategy.com/index.php/';

  public function findCardsIn(string $message) {
    $cards = [];

    foreach(config('cards') as $card) {
        if(preg_match("/\b".strtolower($card)."\b/i", strtolower($message), $matches)) {
          $message = str_replace($card, '', $message);
          $cards[] = $this->addToCards($card);
        }
    }

    return $cards;
  }

  private function addToCards($card) {
    $dominionUrl = self::DOMINION_WIKI_URL;
    $cardImage = "<$dominionUrl$card|Wiki>";
    $cardUrl = config("card_images.$card");
    $cardWiki = "<$cardUrl|Card>";

    return [
        'card_name' => $card,
        'card_image' => $cardImage,
        'image' => $cardUrl,
        'alt_text' => $card,
        'card_wiki' => $cardWiki
    ];
  }
}
