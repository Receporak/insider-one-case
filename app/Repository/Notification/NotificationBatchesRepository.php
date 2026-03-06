<?php

namespace App\Repository\Notification;

use App\Enums\Notifications\NotificationBatchStatus;
use App\Enums\Notifications\NotificationStatus;
use App\Helpers\BaseResponse;
use App\Models\Notification;
use App\Models\NotificationBatches;
use Illuminate\Support\Facades\Log;

class NotificationBatchesRepository
{
    public function __construct(private NotificationBatches $model)
    {
    }

    public function insert(array $datas): BaseResponse
    {
        try {
            $checkExist = NotificationBatches::select('id')
            ->whereIn('status', [NotificationBatchStatus::PENDING->value, NotificationBatchStatus::PROCESSING->value])
            ->get()->pluck('id')->toArray();
        
           $datas = array_filter($datas, function($data) use ($checkExist) {
               return !in_array($data['id'], $checkExist);
           });
           
            $batch = $this->model->insert($datas);
            
            return BaseResponse::success($batch);
        } catch (\Exception $exception) {
            Log::error('NotificationBatchesRepository@insert Exception:', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('Notification Batches Create Exception: ' . $exception->getMessage());
        }
    }

    public function update(string $id, array $data): ?BaseResponse
    {
        try {
            $batch = $this->model->where('id', $id)->first();
            if (!$batch) {
                return BaseResponse::error('Notification Batch not found');
            }
            $batch->update($data);
            return BaseResponse::success($batch);
        } catch (\Exception $exception) {
            Log::error('NotificationBatchesRepository@update Exception:', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('Notification Batches Update Exception: ' . $exception->getMessage());
        }
    }

    /**
     * Verilen batch_id listesi için notification statülerini hesaplar ve batch'i günceller.
     */
    public function resolveAndUpdateStatuses(array $batchIds): void
    {
        if (empty($batchIds)) {
            return;
        }

        try {
            $groupedCounts = Notification::query()
            ->selectRaw('batch_id, status as raw_status, COUNT(*) as cnt')
            ->whereIn('batch_id', $batchIds)
            ->groupBy('batch_id', 'status')
            ->get()
            ->groupBy('batch_id');

            foreach ($batchIds as $batchId) {
                $counts = $groupedCounts->get($batchId, collect())
                    ->pluck('cnt', 'raw_status')
                    ->toArray();
                $status = $this->resolveBatchStatus($counts);
                $this->update($batchId, ['status' => $status->value]);
            }
        } catch (\Exception $exception) {
            Log::error('NotificationBatchesRepository@resolveAndUpdateStatuses Exception:', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            ]);
        }
    }
    /**
     * Statü dağılımına bakarak batch'in durumunu belirlenir
     */
    private function resolveBatchStatus(array $counts): NotificationBatchStatus
    {
        if (isset($counts[NotificationStatus::FAILED->value])) {
            return NotificationBatchStatus::FAILED;
        }

        $total     = array_sum($counts);
        $cancelled = $counts[NotificationStatus::CANCELLED->value] ?? 0;

        if ($total > 0 && $cancelled === $total) {
            return NotificationBatchStatus::CANCELLED;
        }

        return NotificationBatchStatus::COMPLETED;
    }
}