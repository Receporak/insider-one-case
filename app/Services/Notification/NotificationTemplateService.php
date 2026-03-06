<?php

namespace App\Services\Notification;

use App\Helpers\BaseResponse;
use App\Repository\Notification\NotificationTemplateRepository;

class NotificationTemplateService
{
    private NotificationTemplateRepository $repository;

    public function __construct(NotificationTemplateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function create(array $data): ?BaseResponse
    {
        return $this->repository->create($data);
    }

    public function findById(string $id): ?BaseResponse
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): ?BaseResponse
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): ?BaseResponse
    {
        return $this->repository->delete($id);
    }
}