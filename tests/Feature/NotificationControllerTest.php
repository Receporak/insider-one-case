<?php

namespace Tests\Feature;

use App\Helpers\BaseResponse;
use App\Jobs\NotificationQueueManagement;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validPayload = [
            [
                'recipient'   => '905321234567',
                'channel'     => 'sms',
                'priority'    => 'high',
                'template_id' => (string) Str::uuid(),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/notification
    // -------------------------------------------------------------------------

    public function test_insert_returns_201_with_valid_payload(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/notification', $this->validPayload);

        $response->assertStatus(201)
                 ->assertJson(['status' => true, 'code' => 201]);
    }

    public function test_insert_dispatches_notification_queue_management_job(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/notification', $this->validPayload);

        Queue::assertPushed(NotificationQueueManagement::class);
    }

    public function test_insert_accepts_email_channel(): void
    {
        Queue::fake();

        $payload = [[
            'recipient' => 'user@example.com',
            'channel'   => 'email',
            'priority'  => 'normal',
            'content'   => 'Hello',
        ]];

        $response = $this->postJson('/api/v1/notification', $payload);

        $response->assertStatus(201);
    }

    public function test_insert_accepts_push_channel(): void
    {
        Queue::fake();

        $payload = [[
            'recipient' => 'device-token-abc',
            'channel'   => 'push',
            'priority'  => 'low',
            'content'   => 'Push message',
        ]];

        $response = $this->postJson('/api/v1/notification', $payload);

        $response->assertStatus(201);
    }

    public function test_insert_returns_422_for_invalid_channel(): void
    {
        $payload = [[
            'recipient' => '905321234567',
            'channel'   => 'fax',
            'priority'  => 'high',
        ]];

        $response = $this->postJson('/api/v1/notification', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['0.channel']);
    }

    public function test_insert_returns_422_for_invalid_priority(): void
    {
        $payload = [[
            'recipient' => '905321234567',
            'channel'   => 'sms',
            'priority'  => 'urgent',
        ]];

        $response = $this->postJson('/api/v1/notification', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['0.priority']);
    }

    public function test_insert_returns_422_when_recipient_missing(): void
    {
        $payload = [[
            'channel'  => 'sms',
            'priority' => 'high',
        ]];

        $response = $this->postJson('/api/v1/notification', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['0.recipient']);
    }

    public function test_insert_returns_422_when_channel_missing(): void
    {
        $payload = [[
            'recipient' => '905321234567',
            'priority'  => 'high',
        ]];

        $response = $this->postJson('/api/v1/notification', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['0.channel']);
    }

    public function test_insert_returns_422_when_priority_missing(): void
    {
        $payload = [[
            'recipient' => '905321234567',
            'channel'   => 'sms',
        ]];

        $response = $this->postJson('/api/v1/notification', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['0.priority']);
    }

    public function test_insert_returns_422_for_invalid_template_id_format(): void
    {
        $payload = [[
            'recipient'   => '905321234567',
            'channel'     => 'sms',
            'priority'    => 'high',
            'template_id' => 'not-a-uuid',
        ]];

        $response = $this->postJson('/api/v1/notification', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['0.template_id']);
    }

    public function test_insert_accepts_multiple_notifications(): void
    {
        Queue::fake();

        $payload = [
            ['recipient' => '905321234567', 'channel' => 'sms',   'priority' => 'high'],
            ['recipient' => 'u@example.com', 'channel' => 'email', 'priority' => 'normal'],
        ];

        $response = $this->postJson('/api/v1/notification', $payload);

        $response->assertStatus(201);
        Queue::assertPushed(NotificationQueueManagement::class);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/notification
    // -------------------------------------------------------------------------

    public function test_list_returns_200(): void
    {
        $mockResponse = BaseResponse::success(['data' => [], 'total' => 0]);
        $this->mock(NotificationService::class)
             ->shouldReceive('list')
             ->once()
             ->andReturn($mockResponse);

        $response = $this->getJson('/api/v1/notification');

        $response->assertStatus(200)
                 ->assertJson(['status' => true]);
    }

    public function test_list_passes_filters_to_service(): void
    {
        $mockResponse = BaseResponse::success([]);
        $this->mock(NotificationService::class)
             ->shouldReceive('list')
             ->once()
             ->with(\Mockery::on(fn($f) => $f['channel'] === 'sms' && $f['status'] === 'pending'))
             ->andReturn($mockResponse);

        $this->getJson('/api/v1/notification?channel=sms&status=pending');
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/notification/{id}
    // -------------------------------------------------------------------------

    public function test_find_by_id_returns_200_when_found(): void
    {
        $id           = (string) Str::uuid();
        $mockResponse = BaseResponse::success(['id' => $id]);

        $this->mock(NotificationService::class)
             ->shouldReceive('findById')
             ->with($id)
             ->once()
             ->andReturn($mockResponse);

        $response = $this->getJson("/api/v1/notification/{$id}");

        $response->assertStatus(200)
                 ->assertJson(['status' => true]);
    }

    public function test_find_by_id_returns_404_when_not_found(): void
    {
        $id           = (string) Str::uuid();
        $mockResponse = BaseResponse::error('Notification not found', 404);

        $this->mock(NotificationService::class)
             ->shouldReceive('findById')
             ->with($id)
             ->once()
             ->andReturn($mockResponse);

        $response = $this->getJson("/api/v1/notification/{$id}");

        $response->assertStatus(404)
                 ->assertJson(['status' => false]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/notification/findByBatchId/{batchId}
    // -------------------------------------------------------------------------

    public function test_find_by_batch_id_returns_200(): void
    {
        $batchId      = (string) Str::uuid();
        $mockResponse = BaseResponse::success([['id' => Str::uuid()]]);

        $this->mock(NotificationService::class)
             ->shouldReceive('findByBatchId')
             ->with($batchId)
             ->once()
             ->andReturn($mockResponse);

        $response = $this->getJson("/api/v1/notification/findByBatchId/{$batchId}");

        $response->assertStatus(200)
                 ->assertJson(['status' => true]);
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/notification/{id}
    // -------------------------------------------------------------------------

    public function test_update_returns_200(): void
    {
        $id           = (string) Str::uuid();
        $mockResponse = BaseResponse::success(1, 'Notification updated successfully');

        $this->mock(NotificationService::class)
             ->shouldReceive('update')
             ->with($id, \Mockery::any())
             ->once()
             ->andReturn($mockResponse);

        $response = $this->putJson("/api/v1/notification/{$id}", ['status' => 'sent']);

        $response->assertStatus(200)
                 ->assertJson(['status' => true, 'message' => 'Notification updated successfully']);
    }

    public function test_update_returns_422_for_invalid_channel(): void
    {
        $id = (string) Str::uuid();

        $response = $this->putJson("/api/v1/notification/{$id}", ['channel' => 'invalid']);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/notification/{id}
    // -------------------------------------------------------------------------

    public function test_delete_returns_200(): void
    {
        $id           = (string) Str::uuid();
        $mockResponse = BaseResponse::success(1, 'Notification deleted successfully');

        $this->mock(NotificationService::class)
             ->shouldReceive('delete')
             ->with($id)
             ->once()
             ->andReturn($mockResponse);

        $response = $this->deleteJson("/api/v1/notification/{$id}");

        $response->assertStatus(200)
                 ->assertJson(['status' => true, 'message' => 'Notification deleted successfully']);
    }
}
