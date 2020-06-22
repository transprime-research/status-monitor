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
        $failureNotificationSent = false;

        $maxResponseTime = config('monitor.max_allowed_response_time');
        $requestInterval = config('monitor.request_interval');

        $loop = Factory::create();

        $loop->addPeriodicTimer(
            $requestInterval,
            $this->ping($maxResponseTime, $failureCount, $failureNotificationSent)
        );

        $loop->run();
    }

    private function ping(int $maxResponseTime, int $failureCount, bool $failureNotificationSent)
    {
        return function () use ($maxResponseTime, &$failureCount, &$failureNotificationSent) {

            $failed = false;

            $this->warn('Pinging ' . $this->argument('url'));

            [$statusCode, $responseContent] = $this->makeRequest(
                $this->argument('url'),
                $this->argument('content_str'),
                $maxResponseTime
            );

            $this->info('Checking Status code...');

            if ($this->hasRequestFailed($statusCode, $responseContent)) {

                $failed = true;

                $failureCount++;
            }

            if (!$failed && $failureNotificationSent) {

                $this->alert('SITE IS UP');

                $failureCount = 0;
                $failureNotificationSent = false;
            }

            if ($failureCount === 3) {

                $this->alert('SITE IS DOWN');

                $failureCount = 0;

                $failureNotificationSent = true;
            }
        };
    }

    /**
     * @param int $statusCode
     * @param string|null $content
     * @return bool
     */
    private function hasRequestFailed(int $statusCode, ?string $content)
    {
        return (
            Response::HTTP_OK !== $statusCode
            || !str_contains($content, $this->argument('content_str'))
        );
    }

    /**
     * @param string $url
     * @param string $contentString
     * @param string $maxResponseTime
     * @return array
     */
    private function makeRequest($url, $contentString, $maxResponseTime)
    {
        try {
            $response = Http::timeout($maxResponseTime)->get($url, [
                'content_string' => $contentString
            ]);
            $statusCode = $response->status();
            $responseContent = $response->body();
        } catch (ConnectionException $exception) {
            $statusCode = $exception->getCode();
            $responseContent = '';
        }

        return [$statusCode, $responseContent];
    }
}
