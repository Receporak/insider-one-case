<?php

namespace App\Helpers\Notification;

use App\Contracts\NotificationChannelInterface;
use App\Helpers\BaseResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailHelper implements NotificationChannelInterface
{
    public function send(array $notification): BaseResponse
    {
        try {
            $response = Http::timeout(10)
                ->retry(3, 500, function (\Throwable $e, PendingRequest $request) {
                    if ($e instanceof RequestException && $e->response) {
                        return $e->response->status() === 429 || $e->response->serverError();
                    }
                    return true;
                }, throw: false)
                ->withHeaders(['X-Channel' => 'email'])
                ->post(config('services.webhook.url'), [
                    'to'      => $notification['recipient'],
                    'channel' => 'email',
                    'content' => $notification['content'] ?? null,
                ]);

            if ($response->tooManyRequests()) {
                Log::warning('MailHelper@send: rate limited after retries', [
                    'recipient' => $notification['recipient'],
                ]);
                return BaseResponse::error('Mail webhook rate limited', 429);
            }

            if ($response->failed()) {
                Log::warning('MailHelper@send: webhook failed', [
                    'status'    => $response->status(),
                    'recipient' => $notification['recipient'],
                ]);
                return BaseResponse::error('Mail webhook failed: HTTP ' . $response->status(), $response->status());
            }

            return BaseResponse::success($response->body(), 'Mail sent successfully');

        } catch (\Throwable $th) {
            Log::error('MailHelper@send: exception', [
                'message'   => $th->getMessage(),
                'recipient' => $notification['recipient'],
            ]);
            return BaseResponse::error($th->getMessage(), 500);
        }
    }
}