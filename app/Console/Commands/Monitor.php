<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class Monitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:status {url} {content_str}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor this url';

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
        $this->warn('Pinging '.$this->argument('url'));

        $response = Http::timeout(10)->get($this->argument('url'), [
            'content_string' => $this->argument('content_str')
        ]);

        $this->info('Checking Status code');

        if ($response->sta)

        $this->info('Checking content string...');

        if (str_contains($content = $response->body(), $this->argument('content_str'))) {
            $this->info('Found content '.$this->argument('content_str').' in the response.');
            dump($content);

            return ;
        }

        dump('No response existing');
    }
}
