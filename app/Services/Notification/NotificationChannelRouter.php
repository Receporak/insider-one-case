<?php

namespace App\Services\Notification;

use App\Contracts\NotificationChannelInterface;
use App\Enums\Notifications\NotificationChannel;
use App\Enums\Notifications\NotificationStatus;
use App\Helpers\BaseResponse;
use App\Helpers\Notification\MailHelper;
use App\Helpers\Notification\PushHelper;
use App\Helpers\Notification\SmsHelper;
use App\Helpers\Notification\TemplateHelper;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class NotificationChannelRouter
{
    private array $channels;

    public function __construct(
        SmsHelper  $smsHelper,
        MailHelper $mailHelper,
        PushHelper $pushHelper,
    )
    {
        $this->channels = [
            NotificationChannel::SMS->value => $smsHelper,
            NotificationChannel::EMAIL->value => $mailHelper,
            NotificationChannel::PUSH->value => $pushHelper,
        ];
    }

    private array $mockUser = [
        'name'  => 'Recep Orak',
        'email' => 'recep@insider.com',
        'phone' => '+905123456789',
        'city'  => 'Istanbul',
    ];

    /**
     * Notification listesini channel'a göre gruplar, gönderir.
     */
    public function route(array $notifications): BaseResponse
    {
        try {
            $results = [];
            $grouped = $this->groupByChannel($notifications);

            // Tüm batch ID'lerini tek seferde çek
            $allIds = array_column($notifications, 'id');
            $pendingNotifications = Notification::select('id', 'status')
                ->whereIn('id', $allIds)
                ->where('status', NotificationStatus::PENDING)
                ->get()
                ->keyBy('id');

            foreach ($grouped as $channel => $items) {
                $helper = $this->resolve($channel);
                $templateIds = array_column($items, 'template_id');
                $templates = NotificationTemplate::whereIn('id', $templateIds)->get()->pluck('content', 'id');

                foreach ($items as $notification) {
                    $id = $notification['id'];
                    if (!isset($pendingNotifications[$id])) {
                        $results[$id] = NotificationStatus::CANCELLED;
                        continue;
                    }
                    if ($helper === null) {
                        Log::warning("NotificationChannelRouter: unknown channel [{$channel}]", ['id' => $id]);
                        $results[$id] = NotificationStatus::FAILED;
                        continue;
                    }

                    // Kanal bazlı rate limit: webhook.site ~30 req/sn limitine uygun
                    Redis::throttle("rate:channel:{$channel}")
                        ->allow(100)
                        ->every(1)
                        ->then(
                            function () use ($helper, $notification, $id, &$results, $templates) {
                                try {
                                    if (isset($templates[$notification['template_id']])) {
                                        // Template içeriğini doldur
                                        $notification['content'] = TemplateHelper::render(
                                            $templates[$notification['template_id']], $this->mockUser
                                        );
                                    }
                                    $response = $helper->send($notification);
                                    $results[$id] = $response->status
                                        ? NotificationStatus::SENT
                                        : NotificationStatus::FAILED;
                                } catch (\Throwable $e) {
                                    Log::error('NotificationChannelRouter@send error', [
                                        'id' => $id,
                                        'recipient' => $notification['recipient'] ?? null,
                                        'message' => $e->getMessage(),
                                    ]);
                                    $results[$id] = NotificationStatus::FAILED;
                                }
                            },
                            function () use ($id, $channel, &$results) {
                                Log::warning('NotificationChannelRouter: rate limit exceeded', [
                                    'channel' => $channel,
                                    'id' => $id,
                                ]);
                                $results[$id] = NotificationStatus::FAILED;
                            }
                        );
                }
            }
            return BaseResponse::success($results);
        } catch (Exception $exception) {
            Log::error('NotificationChannelRouter@route Exception:', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
            return BaseResponse::error($exception->getMessage());
        }
    }

    private function resolve(string $channel): ?NotificationChannelInterface
    {
        return $this->channels[$channel] ?? null;
    }

    private function groupByChannel(array $notifications): array
    {
        $groups = [];
        foreach ($notifications as $notification) {
            $groups[$notification['channel']][] = $notification;
        }
        return $groups;
    }
}
