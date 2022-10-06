<?php

namespace App\Console\Commands;

use DOMDocument;
use Illuminate\Console\Command;

class FindCardImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cards:find_images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find card images';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = app()->make(\GuzzleHttp\Client::class);

        $file = fopen("/Users/dsokullu/projects/dominion-linker/cards", "w");

        foreach(config('cards') as $card) {
            $cardName = urlencode(str_replace(' ', '_', $card));
            $response = $client->get("http://wiki.dominionstrategy.com/index.php/File:$cardName.jpg");
            $html = new DOMDocument;
            @$html->loadHTML($response->getBody()->getContents());
            $url = $html->getElementById('file')->getElementsByTagName('img')[0]->getAttribute('src');
            $fullUrl = 'http://wiki.dominionstrategy.com' . $url;
            $cardImages[$card] = $fullUrl;

            $line = "\"{$card}\" => \"{$fullUrl}\",";
            $this->output->writeln($line);
            fwrite($file, $line."\n");
        }

        fclose($file);
    }
}
