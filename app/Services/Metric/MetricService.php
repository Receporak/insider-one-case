<?php

namespace App\Services\Metric;

use App\Enums\Notifications\NotificationStatus;
use App\Helpers\BaseResponse;
use App\Repository\Notification\NotificationRepository;

class MetricService
{
    public function __construct(private readonly NotificationRepository $notificationRepository) {}

    public function getNotificationMetrics(): BaseResponse
    {
        return BaseResponse::success([
            'queue_depth' => $this->notificationQueueDepth(),
            'rates'       => $this->notificationSuccessFailureRates(),
            'latency'     => $this->notificationLatency(),
        ]);
    }

    /**
     * Her queue için:
     *  - jobs: Redis'teki job sayısı
     *  - notifications: job payload'larından okunan gerçek notification sayısı
     */
    private function notificationQueueDepth(): array
    {
        $queues = ['high', 'default', 'low'];
        $redis  = \Illuminate\Support\Facades\Redis::connection();

        $result = [];
        $totalJobs          = 0;
        $totalNotifications = 0;

        foreach ($queues as $queue) {
            // Redis list: queues:{queue}
            $rawJobs = $redis->lrange("queues:{$queue}", 0, -1);
            $jobCount  = count($rawJobs);
            $notifCount = 0;

            foreach ($rawJobs as $raw) {
                try {
                    $payload = json_decode($raw, true);
                    $command = unserialize($payload['data']['command'] ?? '');
                    // NotificationProcessor'ın $notifications property'si
                    if ($command instanceof \App\Jobs\NotificationProcessor) {
                        $ref   = new \ReflectionClass($command);
                        $prop  = $ref->getProperty('notifications');
                        $notifCount += count($prop->getValue($command));
                    }
                } catch (\Throwable) {
                    // payload okunamazsa job sayısını baz al
                    $notifCount += 1;
                }
            }

            $result[$queue] = [
                'jobs'          => $jobCount,
                'notifications' => $notifCount,
            ];

            $totalJobs          += $jobCount;
            $totalNotifications += $notifCount;
        }

        $result['total'] = [
            'jobs'          => $totalJobs,
            'notifications' => $totalNotifications,
        ];

        return $result;
    }

    private function notificationSuccessFailureRates(): array
    {
        $counts = $this->notificationRepository->getStatusCounts()->data;

        $sent    = (int) ($counts[NotificationStatus::SENT->value]    ?? 0);
        $failed  = (int) ($counts[NotificationStatus::FAILED->value]  ?? 0);
        $pending = (int) ($counts[NotificationStatus::PENDING->value] ?? 0);
        $cancelled = (int) ($counts[NotificationStatus::CANCELLED->value] ?? 0);
        $total   = $sent + $failed + $pending + $cancelled;

        return [
            'sent'             => $sent,
            'failed'           => $failed,
            'pending'          => $pending,
            'cancelled'        => $cancelled,
            'total'            => $total,
            'success_rate_pct' => $total > 0 ? round(($sent / $total) * 100, 2) : 0.0,
        ];
    }

    private function notificationLatency(): array
    {
        $result = $this->notificationRepository->getSentLatency()->data;

        return [
            'avg_seconds' => $result ? round((float) $result->avg_seconds, 3) : null,
            'min_seconds' => $result ? round((float) $result->min_seconds, 3) : null,
            'max_seconds' => $result ? round((float) $result->max_seconds, 3) : null,
            'count'       => $result ? (int) $result->count : 0,
        ];
    }
}
