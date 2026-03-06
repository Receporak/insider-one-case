<?php

namespace Tests\Unit;

use App\Helpers\BaseResponse;
use Tests\TestCase;

class BaseResponseTest extends TestCase
{
    public function test_success_sets_status_true(): void
    {
        $response = BaseResponse::success();

        $this->assertTrue($response->status);
    }

    public function test_success_default_code_is_200(): void
    {
        $response = BaseResponse::success();

        $this->assertSame(200, $response->code);
    }

    public function test_success_default_message(): void
    {
        $response = BaseResponse::success();

        $this->assertSame('Success', $response->message);
    }

    public function test_success_stores_data(): void
    {
        $data = ['key' => 'value'];

        $response = BaseResponse::success($data);

        $this->assertSame($data, $response->data);
    }

    public function test_success_accepts_custom_message_and_code(): void
    {
        $response = BaseResponse::success(['x'], 'Created', 201);

        $this->assertSame('Created', $response->message);
        $this->assertSame(201, $response->code);
    }

    public function test_error_sets_status_false(): void
    {
        $response = BaseResponse::error();

        $this->assertFalse($response->status);
    }

    public function test_error_default_code_is_400(): void
    {
        $response = BaseResponse::error();

        $this->assertSame(400, $response->code);
    }

    public function test_error_default_message(): void
    {
        $response = BaseResponse::error();

        $this->assertSame('Error', $response->message);
    }

    public function test_error_data_is_null(): void
    {
        $response = BaseResponse::error('Something went wrong', 500);

        $this->assertNull($response->data);
    }

    public function test_error_accepts_custom_message_and_code(): void
    {
        $response = BaseResponse::error('Not found', 404);

        $this->assertSame('Not found', $response->message);
        $this->assertSame(404, $response->code);
    }

    public function test_to_array_contains_all_keys(): void
    {
        $response = BaseResponse::success(['id' => 1], 'OK', 200);

        $array = $response->toArray();

        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('code', $array);
    }

    public function test_to_array_values_match(): void
    {
        $data     = ['id' => 42];
        $response = BaseResponse::success($data, 'Done', 201);

        $array = $response->toArray();

        $this->assertTrue($array['status']);
        $this->assertSame('Done', $array['message']);
        $this->assertSame($data, $array['data']);
        $this->assertSame(201, $array['code']);
    }

    public function test_error_to_array_values_match(): void
    {
        $response = BaseResponse::error('Fail', 503);

        $array = $response->toArray();

        $this->assertFalse($array['status']);
        $this->assertSame('Fail', $array['message']);
        $this->assertNull($array['data']);
        $this->assertSame(503, $array['code']);
    }
}
