<?php

namespace App\Repository\Notification;

use App\Enums\Notifications\NotificationPriority;
use App\Enums\Notifications\NotificationStatus;
use App\Helpers\BaseResponse;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationRepository
{
    public function __construct(private Notification $model)
    {
    }

    /**
     * Idempotency kontrolü yaparak bulk insert eder.
     * Duplicate kayıtlar atlanır, eklenenler (id dahil) döndürülür.
     */
    public function insert(array $data): BaseResponse
    {
        try {
            $rows = array_map(fn(array $item) => [
                'id'          => (string) Str::uuid(),
                'recipient'   => $item['recipient'],
                'channel'     => $item['channel'],
                'priority'    => $item['priority'] ?? NotificationPriority::NORMAL->value,
                'template_id' => $item['template_id'] ?? null,
                'content'     => $item['content'] ?? null,
                'status'      => NotificationStatus::PENDING->value,
                'batch_id'    => $item['batch_id'] ?? null,
            ], $data);


            // 1. Batch içi duplicate'leri çıkar
            $rows = $this->deduplicateInMemory($rows);

            if (empty($rows)) {
                return BaseResponse::success([], 'All notifications are duplicates, nothing inserted', 200);
            }

            // 2. DB'de zaten var olan kayıtları çıkar
            $rows = $this->filterExisting($rows);

            if (empty($rows)) {
                return BaseResponse::success([], 'All notifications already exist', 200);
            }

            // 3. insertOrIgnore: race condition'a karşı güvenlik ağı
            $this->model->insertOrIgnore($rows);

            return BaseResponse::success($rows, 'Notification inserted successfully', 200);
        } catch (\Exception $exception) {
            Log::error('NotificationRepository@insert Exception:', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('Notification Insert Exception: ' . $exception->getMessage());
        }
    }

    public function list(array $filter): BaseResponse
    {
        try {
            $query = $this->model->query();

            if (!empty($filter['channel'])) {
                $query->where('channel', $filter['channel']);
            }
            if (!empty($filter['status'])) {
                $query->where('status', $filter['status']);
            }
            if (!empty($filter['batch_id'])) {
                $query->where('batch_id', $filter['batch_id']);
            }
            if (!empty($filter['first_date'])) {
                $query->where('created_at','>=', $filter['first_date']);
            }
            if (!empty($filter['last_date'])) {
                $query->where('created_at','<=', $filter['last_date']);
            }
            $result = $query->paginate($filter['item_per_page'] ?? 15, ['*'], 'page', $filter['page'] ?? 1);
            return BaseResponse::success($result);
        } catch (\Exception $exception) {
            Log::error('NotificationRepository@list Exception:', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('Notification List Exception: ' . $exception->getMessage());
        }
    }

    /**
     * Batch içi duplicate'leri çıkarır.
     * Unique key: channel|recipient|template_id ya da channel|recipient|md5(content)
     */
    private function deduplicateInMemory(array $rows): array
    {
        $seen        = [];
        $unique      = [];
        $duplicateKeys = [];

        foreach ($rows as $row) {
            $key = $this->idempotencyKey($row);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $row;
            } else {
                $duplicateKeys[] = $key;
            }
        }

        if (!empty($duplicateKeys)) {
            Log::info('NotificationRepository@deduplicateInMemory: batch-içi duplicate\'ler atlandı', [
                'duplicate_keys' => $duplicateKeys,
            ]);
        }

        return $unique;
    }

    /**
     *   1. Gelen batch'teki recipient setini çıkar
     *   2. Bu recipient'lara ait mevcut pending/sent kayıtları TEK sorguda çek
     *   3. idempotencyKey hash map'ini PHP'de oluştur
     *   4. Gelen batch'i filtrele
     */
    private function filterExisting(array $rows): array
    {
        $recipients = array_unique(array_column($rows, 'recipient'));
        $channels   = array_unique(array_column($rows, 'channel'));

        $existingKeys = $this->model
            ->whereIn('recipient', $recipients)
            ->whereIn('channel',   $channels)
            ->whereIn('status', [
                NotificationStatus::PENDING,
                NotificationStatus::SENT,
            ])
            ->where('created_at', '>=', now()->subDays(3))
            ->get(['channel', 'recipient', 'template_id', 'content'])
            ->mapWithKeys(fn($n) => [$this->idempotencyKey($n->toArray()) => true])
            ->all();

        $duplicateKeys = [];
        $newRows       = array_values(
            array_filter($rows, function ($row) use ($existingKeys, &$duplicateKeys) {
                $key = $this->idempotencyKey($row);
                if (isset($existingKeys[$key])) {
                    $duplicateKeys[] = $key;
                    return false;
                }
                return true;
            })
        );

        if (!empty($duplicateKeys)) {
            Log::info('NotificationRepository@filterExisting: Dublicate values', [
                'duplicate_keys' => $duplicateKeys,
            ]);
        }

        return $newRows;
    }

    /**
     * template_id varsa: "channel|recipient|template_id"
     * template_id yoksa:  "channel|recipient|md5(content)"
     */
    private function idempotencyKey(array $row): string
    {
        if (!empty($row['template_id'])) {
            return implode('|', [$row['channel'], $row['recipient'], $row['template_id']]);
        }

        return implode('|', [$row['channel'], $row['recipient'], md5($row['content'] ?? '')]);
    }

    /**
     * Verilen ID'lerin statüsünü toplu günceller.
     *
     * @param string[] $ids
     */
    public function updateStatuses(array $ids, NotificationStatus $status): void
    {
        try {
            $this->model
                ->whereIn('id', $ids)
                ->update([
                    'status' => $status->value,
                ]);
        } catch (\Exception $exception) {
            Log::error('NotificationRepository@updateStatuses Exception:', [
                'message' => $exception->getMessage(),
                'ids'     => $ids,
                'status'  => $status->value,
            ]);
        }
    }

    public function findByRecipient(string $recipient): ?BaseResponse
    {
        try {
            $notification = $this->model->where('recipient', $recipient)->first();

            return BaseResponse::success($notification);
        } catch (\Exception $exception) {
            Log::error('NotificationRepository@FindByRecipient Exception:', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('Notification FindByRecipient Exception: ' . $exception->getMessage());
        }
    }

    public function findById(string $id): BaseResponse
    {
        try {
            $notification = $this->model->find($id);

            if (!$notification) {
                return BaseResponse::error('Notification not found', 404);
            }

            return BaseResponse::success($notification);
        } catch (\Exception $exception) {
            Log::error('NotificationRepository@findById Exception:', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            ]);

            return BaseResponse::error('Notification FindById Exception: ' . $exception->getMessage());
        }
    }

    public function findByBatchId(string $batchId): BaseResponse
    {
        try {
            $notifications = $this->model->where('batch_id', $batchId)->get();

            return BaseResponse::success($notifications);
        } catch (\Exception $exception) {
            Log::error('NotificationRepository@findByBatchId Exception:', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            ]);

            return BaseResponse::error('Notification FindByBatchId Exception: ' . $exception->getMessage());
        }
    }

    public function update(string $id, array $data): ?BaseResponse
    {
        try {
            $notification = $this->model->where('id', $id)->update($data);

            return BaseResponse::success($notification, 'Notification updated successfully', 200);
        } catch (\Exception $exception) {
            Log::error('NotificationRepository@Update Exception:', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('Notification Update Exception: ' . $exception->getMessage());
        }
    }

    public function updateByBatchId(string $batchId, array $data): ?BaseResponse
    {
        try {
            $notification = $this->model->where('batch_id', $batchId)->update($data);

            return BaseResponse::success($notification, 'Notification updated successfully', 200);
        } catch (\Exception $exception) {
            Log::error('NotificationRepository@UpdateByBatchId Exception:', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('Notification UpdateByBatchId Exception: ' . $exception->getMessage());
        }
    }

    public function delete(string $id): ?BaseResponse
    {
        try {
            $notification = $this->model->where('id', $id)->delete();

            return BaseResponse::success($notification, 'Notification deleted successfully', 200);
        } catch (\Exception $exception) {
            Log::error('NotificationRepository@Delete Exception:', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('Notification Delete Exception: ' . $exception->getMessage());
        }
    }

    public function getStatusCounts(): BaseResponse
    {
         try{
            $query = Notification::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

            return BaseResponse::success($query, 'Notification status counts fetched successfully', 200);
         }catch (\Exception $exception) {
            Log::error('NotificationRepository@GetStatusCounts Exception:', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('Notification GetStatusCounts Exception: ' . $exception->getMessage());
        }
    }

    public function getSentLatency(): BaseResponse
    {
        try{
            $query = Notification::query()
            ->where('status', NotificationStatus::SENT->value)
            ->selectRaw('
                AVG(EXTRACT(EPOCH FROM (updated_at - created_at))) AS avg_seconds,
                MIN(EXTRACT(EPOCH FROM (updated_at - created_at))) AS min_seconds,
                MAX(EXTRACT(EPOCH FROM (updated_at - created_at))) AS max_seconds,
                COUNT(*) AS count
            ')
            ->first();
            
            return BaseResponse::success($query, 'Notification sent latency fetched successfully', 200);
        }catch (\Exception $exception) {
            Log::error('NotificationRepository@GetSentLatency Exception:', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('Notification GetSentLatency Exception: ' . $exception->getMessage());
        }
    }
}