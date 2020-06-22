<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Response;
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
        $failureCount = 0;
        $limit = 5;

        while ($limit > 0) {
            $limit --;

            $failed = false;

            $this->warn('Pinging ' . $this->argument('url'));

            $response = Http::timeout(10)->get($this->argument('url'), [
                'content_string' => $this->argument('content_str')
            ]);

            $this->info('Checking Status code');

            if ($response->status() !== Response::HTTP_OK) {
                $failed = true;

                $failureCount ++;
            }

            if ($failureCount >= 3) {
                $this->alert('SITE IS DOWN');

                $failureCount = 0;
            }

            if ($failed) {
                continue;
            }

            $this->info('Checking content string...');

            if (str_contains($content = $response->body(), $this->argument('content_str'))) {
                $this->info('Found content ' . $this->argument('content_str') . ' in the response.');
                dump($content);

                continue;
            }

            dump('No response existing');
        }

    }
}
