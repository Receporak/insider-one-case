<?php

namespace App\Services\Notification;

use App\Helpers\BaseResponse;
use App\Repository\Notification\NotificationBatchesRepository;

class NotificationBatchService
{
    private NotificationBatchesRepository $repository;

    public function __construct(NotificationBatchesRepository $repository)
    {
        $this->repository = $repository;
    }

    public function insert(array $data): BaseResponse
    {
        return $this->repository->insert($data);
    }

    public function update(string $id, array $data): BaseResponse
    {
        return $this->repository->update($id, $data);
    }

    public function resolveAndUpdateStatuses(array $batchIds): void
    {
        $this->repository->resolveAndUpdateStatuses($batchIds);
    }
}