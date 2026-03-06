<?php

namespace App\Jobs;

use App\Enums\Notifications\NotificationBatchStatus;
use App\Enums\Notifications\NotificationPriority;
use App\Services\Notification\NotificationBatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Dispatcher job: Gelen bildirimleri priority'e göre gruplar
 * ve her grubu kendi queue'suna (high / default / low) iletir.
 */
class NotificationQueueManagement implements ShouldQueue
{
    use Queueable;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(NotificationBatchService $batchService): void
    {
        try {
            $grouped = $this->groupByPriority($this->data);
            $batches = [];

            foreach ($grouped as $priorityValue => $notifications) {
                $priority  = NotificationPriority::from($priorityValue);
                $queueName = $this->resolveQueue($priority);

                foreach ($notifications as $notification) {
                    $batchId = $notification['batch_id'] ?? null;
                    if ($batchId && !isset($batches[$batchId])) {
                        $batches[$batchId] = [
                            'id'     => $batchId,
                            'status' => NotificationBatchStatus::PENDING->value,
                        ];
                    }
                }

                NotificationProcessor::dispatch($notifications, $priority)
                    ->onQueue($queueName);
            }

            if (!empty($batches)) {
                $batchService->insert(array_values($batches));
            }

        } catch (\Exception $exception) {
            Log::error('NotificationQueueManagement@handle Exception:', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ]);
        }
    }

    /**
     * Bildirimleri priority değerine göre gruplama işlemi
     */
    private function groupByPriority(array $data): array
    {
        $groups = [];

        foreach ($data as $notification) {
            $priority            = $notification['priority'] ?? NotificationPriority::NORMAL->value;
            $groups[$priority][] = $notification;
        }

        return $groups;
    }

    /**
     * Priority enum'unu Supervisor'da tanımlı queue adına çevirme işlemi
     */
    private function resolveQueue(NotificationPriority $priority): string
    {
        return match ($priority) {
            NotificationPriority::HIGH   => 'high',
            NotificationPriority::NORMAL => 'default',
            NotificationPriority::LOW    => 'low',
        };
    }
}
