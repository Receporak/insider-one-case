<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_api_is_reachable(): void
    {
        DB::shouldReceive('connection->getPdo')->once()->andReturnSelf();
        DB::shouldReceive('statement')->with('SELECT 1')->once()->andReturn(true);
        Redis::shouldReceive('ping')->once()->andReturn(true);
        Queue::shouldReceive('size')->once()->andReturn(0);

        $this->getJson('/api/v1/health')->assertStatus(200);
    }
}
