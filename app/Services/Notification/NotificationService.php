<?php

namespace App\Services\Notification;

use App\Enums\Notifications\NotificationStatus;
use App\Helpers\BaseResponse;
use App\Repository\Notification\NotificationRepository;

class NotificationService
{
    private NotificationRepository $repository;

    public function __construct(NotificationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function insert(array $data): BaseResponse
    {
        return $this->repository->insert($data);
    }

    public function list(array $filter): BaseResponse
    {
        return $this->repository->list($filter);
    }

    public function findByRecipient(string $recipient): BaseResponse
    {
        return $this->repository->findByRecipient($recipient);
    }

    public function findById(string $id): BaseResponse
    {
        return $this->repository->findById($id);
    }

    public function findByBatchId(string $batchId): BaseResponse
    {
        return $this->repository->findByBatchId($batchId);
    }

    public function update(string $id, array $data): ?BaseResponse
    {
        return $this->repository->update($id, $data);
    }

    public function updateByBatchId(string $batchId, array $data): ?BaseResponse
    {
        return $this->repository->updateByBatchId($batchId, $data);
    }

    public function updateStatuses(array $ids, NotificationStatus $status): void
    {
        $this->repository->updateStatuses($ids, $status);
    }

    public function delete(string $id): ?BaseResponse
    {
        return $this->repository->delete($id);
    }
}