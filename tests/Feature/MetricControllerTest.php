<?php

namespace Tests\Feature;

use App\Helpers\BaseResponse;
use App\Services\Metric\MetricService;
use Tests\TestCase;

class MetricControllerTest extends TestCase
{
    private function metricPayload(): array
    {
        return [
            'queue_depth' => [
                'high'    => ['jobs' => 2, 'notifications' => 5],
                'default' => ['jobs' => 4, 'notifications' => 10],
                'low'     => ['jobs' => 1, 'notifications' => 2],
                'total'   => ['jobs' => 7, 'notifications' => 17],
            ],
            'rates' => [
                'sent'             => 150,
                'failed'           => 10,
                'pending'          => 20,
                'cancelled'        => 0,
                'total'            => 180,
                'success_rate_pct' => 83.33,
            ],
            'latency' => [
                'avg_seconds' => 2.45,
                'min_seconds' => 0.12,
                'max_seconds' => 15.3,
                'count'       => 150,
            ],
        ];
    }

    public function test_get_notification_metric_returns_200(): void
    {
        $mockResponse = BaseResponse::success($this->metricPayload());

        $this->mock(MetricService::class)
             ->shouldReceive('getNotificationMetrics')
             ->once()
             ->andReturn($mockResponse);

        $response = $this->getJson('/api/v1/metrics/notifications');

        $response->assertStatus(200);
    }

    public function test_response_contains_queue_depth(): void
    {
        $mockResponse = BaseResponse::success($this->metricPayload());

        $this->mock(MetricService::class)
             ->shouldReceive('getNotificationMetrics')
             ->once()
             ->andReturn($mockResponse);

        $response = $this->getJson('/api/v1/metrics/notifications');

        $response->assertJsonStructure(['data' => ['queue_depth', 'rates', 'latency']]);
    }

    public function test_response_contains_success_rate(): void
    {
        $mockResponse = BaseResponse::success($this->metricPayload());

        $this->mock(MetricService::class)
             ->shouldReceive('getNotificationMetrics')
             ->once()
             ->andReturn($mockResponse);

        $response = $this->getJson('/api/v1/metrics/notifications');

        $response->assertJsonPath('data.rates.success_rate_pct', 83.33);
    }

    public function test_returns_500_when_service_throws(): void
    {
        $this->mock(MetricService::class)
             ->shouldReceive('getNotificationMetrics')
             ->once()
             ->andThrow(new \Exception('Redis down'));

        $response = $this->getJson('/api/v1/metrics/notifications');

        $response->assertStatus(500);
    }
}
