<?php

namespace Tests\Unit;

use Tests\TestCase;
use Mockery\MockInterface;
use App\Console\Commands\Monitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Console\Kernel;

class MonitorTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testMonitorReceivesACall()
    {
        $mocked = $this->mock(Monitor::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once();

            $mock->shouldReceive('argument')
                ->with(['url'])
                ->andReturn('');

            $mock->shouldReceive('argument')
                ->with(['content_str'])
                ->andReturn('value1');
        });

        $mocked->handle();
    }

    public function testSiteIsDownNotificationCameUp()
    {
        $kernel = $this->app->make(Kernel::class);

        try {
            Http::fakeSequence()
                ->push('Hello World', 400)
                ->push('Hello World', 400)
                ->push('Hello World', 400);

            $kernel->call('monitor:status https://omitobisam1.com World');
        } catch (\OutOfBoundsException $exception) {
            $this->assertStringContainsString('SITE IS DOWN', $kernel->output());
        }
    }

    public function testSiteIsUpNotificationCameUp()
    {
        $kernel = $this->app->make(Kernel::class);

        try {
            Http::fakeSequence()
                ->push('Hello World', 400)
                ->push('Hello World', 400)
                ->push('Hello World', 400)
                ->push('Hello World', 200);

            $kernel->call('monitor:status https://omitobisam1.com World')->terminate();
        } catch (\OutOfBoundsException $exception) {
            $output = (string)$kernel->output();
            $this->assertStringContainsString('SITE IS DOWN', $output);
            $this->assertStringContainsString('SITE IS UP', $output);
        }
    }

    public function testSiteIsUpAfterARoundOfFailureNotification()
    {
        $kernel = $this->app->make(Kernel::class);

        try {
            Http::fakeSequence()
                ->push('Hello World', 400)
                ->push('Hello World', 400)
                ->push('Hello World', 400)
                ->push('Hello World', 400)
                ->push('Hello World', 200);

            $kernel->call('monitor:status https://omitobisam1.com World');
        } catch (\OutOfBoundsException $exception) {

            $output = (string)$kernel->output();

            $this->assertStringContainsString('SITE IS DOWN', $output);
            $this->assertStringContainsString('SITE IS UP', $output);

            $this->assertEquals(1, substr_count($output, 'SITE IS DOWN'));
            $this->assertEquals(1, substr_count($output, 'SITE IS UP'));
        }
    }

    public function testSiteIsDownNotificationCameUpBecauseOfWrongContent()
    {
        $kernel = $this->app->make(Kernel::class);

        try {
            Http::fakeSequence()
                ->push('Hello World', 200)
                ->push('Hello World', 200)
                ->push('Hello World', 200);

            $kernel->call('monitor:status https://site.example Ninja');
        } catch (\OutOfBoundsException $exception) {

            $output = (string)$kernel->output();

            $this->assertStringContainsString('SITE IS DOWN', $output);

            $this->assertEquals(1, substr_count($output, 'SITE IS DOWN'));
        }
    }

//    public function testSiteIsDownNotificationCameUpBecauseOfTimeOut()
//    {
//        Http::fake()
//
//        $kernel = $this->app->make(Kernel::class);
//
//        $kernel->call('monitor:status https://site.example Hello');
//
//        $output = (string)$kernel->output();
//
//        $this->assertStringContainsString('SITE IS DOWN', $output);
//
//        $this->assertEquals(1, substr_count($output, 'SITE IS DOWN'));
//    }
}
