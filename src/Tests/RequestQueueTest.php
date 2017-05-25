<?php

namespace LittleBookBoy\Request\Queue\Tests;

use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class RequestQueueTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_request_queue()
    {
        for ($i = 0; $i < 1; $i++) {

            $parameters = [
                '_callback_url' => 'google.url' . str_random(5) . '.com.tw',
                '_callback_token' => str_random(20),
            ];

            $server = [
                'HTTP_X-Correlation-ID' => Uuid::uuid4()->toString()
            ];

            // 測試 PATCH
            $this->call('PATCH', '/api/user/1', $parameters, [], [], $server);
        }
    }
}
