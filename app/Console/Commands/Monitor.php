<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use React\EventLoop\Factory;

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

        $maxResponseTime = config('monitor.max_allowed_response_time');
        $requestInterval = config('monitor.request_interval');

        $loop = Factory::create();

        $loop->addPeriodicTimer($requestInterval, function () use ($maxResponseTime, &$failureCount){

            $failed = false;

            $this->warn('Pinging ' . $this->argument('url'));

            try {
                $response = Http::timeout($maxResponseTime)->get($this->argument('url'), [
                    'content_string' => $this->argument('content_str')
                ]);
                $statusCode = $response->status();
                $responseContent = $response->body();
            } catch (ConnectionException $exception) {
                $statusCode = $exception->getCode();
                $responseContent = '';
            }

            $this->info('Checking Status code');

            if (Response::HTTP_OK !== $statusCode) {
                $failed = true;

                $failureCount ++;
            }

            if ($failureCount >= 3) {
                $this->alert('SITE IS DOWN');

                $failureCount = 0;
            }

            if ($failed) {
                return;
            }

            $this->info('Checking content string...');

            if (str_contains($responseContent, $this->argument('content_str'))) {
                $this->info('Found content ' . $this->argument('content_str') . ' in the response.');
                dump($responseContent);

                return;
            }

            dump('No response existing');
        });

        $loop->run();
    }
}
