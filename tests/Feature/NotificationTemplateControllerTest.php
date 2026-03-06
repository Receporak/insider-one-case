<?php

namespace Tests\Feature;

use App\Helpers\BaseResponse;
use App\Services\Notification\NotificationTemplateService;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTemplateControllerTest extends TestCase
{
    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validPayload = [
            'name'    => 'order_shipped',
            'channel' => 'sms',
            'content' => 'Siparişiniz kargoya verildi.',
            'status'  => 'active',
        ];
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/notification-template
    // -------------------------------------------------------------------------

    public function test_create_returns_response_from_service(): void
    {
        $mockResponse = BaseResponse::success(['id' => Str::uuid()], 'Template created', 201);

        $this->mock(NotificationTemplateService::class)
             ->shouldReceive('create')
             ->once()
             ->andReturn($mockResponse);

        $response = $this->postJson('/api/v1/notification-template', $this->validPayload);

        $response->assertStatus(201)
                 ->assertJson(['status' => true]);
    }

    public function test_create_returns_422_when_name_missing(): void
    {
        $payload = $this->validPayload;
        unset($payload['name']);

        $response = $this->postJson('/api/v1/notification-template', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_create_returns_422_for_invalid_channel(): void
    {
        $payload            = $this->validPayload;
        $payload['channel'] = 'fax';

        $response = $this->postJson('/api/v1/notification-template', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['channel']);
    }

    public function test_create_returns_422_for_invalid_status(): void
    {
        $payload           = $this->validPayload;
        $payload['status'] = 'draft';

        $response = $this->postJson('/api/v1/notification-template', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);
    }

    public function test_create_returns_422_when_content_missing(): void
    {
        $payload = $this->validPayload;
        unset($payload['content']);

        $response = $this->postJson('/api/v1/notification-template', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['content']);
    }

    public function test_create_accepts_all_valid_channels(): void
    {
        $mockResponse = BaseResponse::success([], 'Created', 201);
        $this->mock(NotificationTemplateService::class)
             ->shouldReceive('create')
             ->times(3)
             ->andReturn($mockResponse);

        foreach (['sms', 'email', 'push'] as $channel) {
            $payload            = $this->validPayload;
            $payload['channel'] = $channel;

            $this->postJson('/api/v1/notification-template', $payload)
                 ->assertStatus(201);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/notification-template/{id}
    // -------------------------------------------------------------------------

    public function test_find_by_id_returns_200_when_found(): void
    {
        $id           = (string) Str::uuid();
        $mockResponse = BaseResponse::success(['id' => $id, 'name' => 'test']);

        $this->mock(NotificationTemplateService::class)
             ->shouldReceive('findById')
             ->with($id)
             ->once()
             ->andReturn($mockResponse);

        $response = $this->getJson("/api/v1/notification-template/{$id}");

        $response->assertStatus(200)
                 ->assertJson(['status' => true]);
    }

    public function test_find_by_id_returns_error_when_not_found(): void
    {
        $id           = (string) Str::uuid();
        $mockResponse = BaseResponse::error('Template not found', 404);

        $this->mock(NotificationTemplateService::class)
             ->shouldReceive('findById')
             ->with($id)
             ->once()
             ->andReturn($mockResponse);

        $response = $this->getJson("/api/v1/notification-template/{$id}");

        $response->assertStatus(404)
                 ->assertJson(['status' => false]);
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/notification-template/{id}
    // -------------------------------------------------------------------------

    public function test_update_returns_200(): void
    {
        $id           = (string) Str::uuid();
        $mockResponse = BaseResponse::success(1, 'Template updated');

        $this->mock(NotificationTemplateService::class)
             ->shouldReceive('update')
             ->with($id, \Mockery::any())
             ->once()
             ->andReturn($mockResponse);

        $response = $this->putJson("/api/v1/notification-template/{$id}", $this->validPayload);

        $response->assertStatus(200)
                 ->assertJson(['status' => true]);
    }

    public function test_update_returns_422_for_invalid_channel(): void
    {
        $id      = (string) Str::uuid();
        $payload = $this->validPayload;
        $payload['channel'] = 'telegraph';

        $response = $this->putJson("/api/v1/notification-template/{$id}", $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['channel']);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/notification-template/{id}
    // -------------------------------------------------------------------------

    public function test_delete_returns_200(): void
    {
        $id           = (string) Str::uuid();
        $mockResponse = BaseResponse::success(1, 'Template deleted');

        $this->mock(NotificationTemplateService::class)
             ->shouldReceive('delete')
             ->with($id)
             ->once()
             ->andReturn($mockResponse);

        $response = $this->deleteJson("/api/v1/notification-template/{$id}");

        $response->assertStatus(200)
                 ->assertJson(['status' => true]);
    }
}
