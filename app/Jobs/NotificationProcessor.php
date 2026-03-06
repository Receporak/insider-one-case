<?php

namespace App\Jobs;

use App\Enums\Notifications\NotificationPriority;
use App\Enums\Notifications\NotificationStatus;
use App\Services\Notification\NotificationBatchService;
use App\Services\Notification\NotificationChannelRouter;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotificationProcessor implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private readonly array                $notifications,
        private readonly NotificationPriority $priority,
    ) {
    }

    public function handle(NotificationService $service, NotificationChannelRouter $router, NotificationBatchService $batchService): void
    {
        Log::info('NotificationProcessor@handle', [
            'priority' => $this->priority->value,
            'count'    => count($this->notifications),
        ]);

        // Toplu Kayıt
        $inserted = $service->insert($this->notifications);

        if (!$inserted->status) {
            Log::error('NotificationProcessor@handle-inserted-error', [
                'message' => $inserted->message,
            ]);
            return;
        }
        if (count($inserted->data) == 0) {
            Log::info('NotificationProcessor@handle-inserted-empty', [
                'message' => $inserted->message,
            ]);
            return;
        }

        // Kanal'a Gönder (Router artık BaseResponse dönüyor)
        $routeResponse = $router->route($inserted->data);

        // Eğer router'ın kendisi exception fırlatıp Error Response döndüyse job'ı iptal et
        if (!$routeResponse->status) {
            Log::error('NotificationProcessor@handle-router-error', [
                'message' => $routeResponse->message,
            ]);
        }

        $results = $routeResponse->data ?? [];

        // Sonuçları ID Gruplarına Gönder
        $sentIds      = [];
        $failedIds    = [];
        $cancelledIds = [];

        foreach ($results as $id => $status) {
            match ($status) {
                NotificationStatus::SENT      => $sentIds[]      = $id,
                NotificationStatus::CANCELLED => $cancelledIds[] = $id,
                default                       => $failedIds[]    = $id,
            };
        }

        // Statü Güncelle
        if (!empty($sentIds)) {
            $service->updateStatuses($sentIds, NotificationStatus::SENT);
        }
        if (!empty($failedIds)) {
            $service->updateStatuses($failedIds, NotificationStatus::FAILED);
        }
        if (!empty($cancelledIds)) {
            $service->updateStatuses($cancelledIds, NotificationStatus::CANCELLED);
        }

        // Batch'lerin Statü Güncelle
        $affectedBatchIds = array_unique(array_filter(
            array_column($inserted->data, 'batch_id')
        ));

        $batchService->resolveAndUpdateStatuses($affectedBatchIds);

        Log::info('NotificationProcessor@handle completed', [
            'sent'      => count($sentIds),
            'failed'    => count($failedIds),
            'cancelled' => count($cancelledIds),
            'batches'   => count($affectedBatchIds),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('NotificationProcessor@failed', [
            'priority' => $this->priority->value,
            'message'  => $exception->getMessage(),
            'trace'    => $exception->getTraceAsString(),
            'line'     => $exception->getLine(),
            'file'     => $exception->getFile(),
        ]);
    }
}
