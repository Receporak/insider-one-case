<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    private function mockAllServicesUp(): void
    {
        DB::shouldReceive('connection->getPdo')->once()->andReturnSelf();
        DB::shouldReceive('statement')->with('SELECT 1')->once()->andReturn(true);
        Redis::shouldReceive('ping')->once()->andReturn(true);
        Queue::shouldReceive('size')->once()->andReturn(0);
    }

    public function test_returns_200_when_all_services_healthy(): void
    {
        $this->mockAllServicesUp();

        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
    }

    public function test_response_has_base_response_structure(): void
    {
        $this->mockAllServicesUp();

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonStructure(['status', 'message', 'data', 'code']);
    }

    public function test_status_is_true_when_healthy(): void
    {
        $this->mockAllServicesUp();

        $response = $this->getJson('/api/v1/health');

        $response->assertJson(['status' => true, 'code' => 200]);
    }

    public function test_data_contains_service_statuses(): void
    {
        $this->mockAllServicesUp();

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonStructure([
            'data' => [
                'status',
                'timestamp',
                'services' => [
                    'database' => ['status', 'message'],
                    'redis'    => ['status', 'message'],
                    'queue'    => ['status', 'message'],
                ],
            ],
        ]);
    }

    public function test_all_services_are_up_when_healthy(): void
    {
        $this->mockAllServicesUp();

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonPath('data.services.database.status', 'up')
                 ->assertJsonPath('data.services.redis.status', 'up')
                 ->assertJsonPath('data.services.queue.status', 'up')
                 ->assertJsonPath('data.status', 'healthy');
    }

    public function test_queue_includes_pending_jobs_count(): void
    {
        DB::shouldReceive('connection->getPdo')->once()->andReturnSelf();
        DB::shouldReceive('statement')->with('SELECT 1')->once()->andReturn(true);
        Redis::shouldReceive('ping')->once()->andReturn(true);
        Queue::shouldReceive('size')->once()->andReturn(7);

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonPath('data.services.queue.pending_jobs', 7);
    }

    public function test_returns_503_when_database_is_down(): void
    {
        DB::shouldReceive('connection->getPdo')->once()->andThrow(new \Exception('Connection refused'));
        Redis::shouldReceive('ping')->once()->andReturn(true);
        Queue::shouldReceive('size')->once()->andReturn(0);

        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(503);
    }

    public function test_status_is_false_when_database_is_down(): void
    {
        DB::shouldReceive('connection->getPdo')->once()->andThrow(new \Exception('Connection refused'));
        Redis::shouldReceive('ping')->once()->andReturn(true);
        Queue::shouldReceive('size')->once()->andReturn(0);

        $response = $this->getJson('/api/v1/health');

        $response->assertJson(['status' => false, 'code' => 503])
                 ->assertJsonPath('data.services.database.status', 'down')
                 ->assertJsonPath('data.status', 'unhealthy');
    }

    public function test_returns_503_when_redis_is_down(): void
    {
        DB::shouldReceive('connection->getPdo')->once()->andReturnSelf();
        DB::shouldReceive('statement')->with('SELECT 1')->once()->andReturn(true);
        Redis::shouldReceive('ping')->once()->andThrow(new \Exception('Redis connection failed'));
        Queue::shouldReceive('size')->once()->andReturn(0);

        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(503)
                 ->assertJsonPath('data.services.redis.status', 'down');
    }

    public function test_returns_503_when_queue_is_down(): void
    {
        DB::shouldReceive('connection->getPdo')->once()->andReturnSelf();
        DB::shouldReceive('statement')->with('SELECT 1')->once()->andReturn(true);
        Redis::shouldReceive('ping')->once()->andReturn(true);
        Queue::shouldReceive('size')->once()->andThrow(new \Exception('Queue unavailable'));

        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(503)
                 ->assertJsonPath('data.services.queue.status', 'down');
    }

    public function test_database_down_message_is_included(): void
    {
        DB::shouldReceive('connection->getPdo')->once()->andThrow(new \Exception('SQLSTATE error'));
        Redis::shouldReceive('ping')->once()->andReturn(true);
        Queue::shouldReceive('size')->once()->andReturn(0);

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonPath('data.services.database.message', 'SQLSTATE error');
    }
}
