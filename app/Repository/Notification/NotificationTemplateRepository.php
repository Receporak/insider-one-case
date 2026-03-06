<?php

namespace App\Repository\Notification;

use App\Helpers\BaseResponse;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

class NotificationTemplateRepository
{
    public function __construct(private NotificationTemplate $model)
    {
    }

    public function create(array $data): ?BaseResponse
    {
        try {
            $notificationTemplate = $this->model->create($data);

            return BaseResponse::success($notificationTemplate, 'NotificationTemplate created successfully', 201);
        } catch (\Exception $exception) {
            Log::error('NotificationTemplateRepository@Create Exception:', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('NotificationTemplate Create Exception: ' . $exception->getMessage());
        }
    }

    public function findById(string $id): ?BaseResponse
    {
        try {
            $notification = $this->model->where('id', $id)->first();

            return BaseResponse::success($notification);
        } catch (\Exception $exception) {
            Log::error('NotificationTemplateRepository@FindById Exception:', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('NotificationTemplate FindById Exception: ' . $exception->getMessage());
        }
    }

    public function update(string $id, array $data): ?BaseResponse
    {
        try {
            $notification = $this->model->where('id', $id)->update($data);

            return BaseResponse::success($notification, 'NotificationTemplate updated successfully', 200);
        } catch (\Exception $exception) {
            Log::error('NotificationTemplateRepository@Update Exception:', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('NotificationTemplate Update Exception: ' . $exception->getMessage());
        }
    }

    public function delete(string $id): ?BaseResponse
    {
        try {
            $notification = $this->model->where('id', $id)->delete();

            return BaseResponse::success($notification, 'NotificationTemplate deleted successfully', 200);
        } catch (\Exception $exception) {
            Log::error('NotificationTemplateRepository@Delete Exception:', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return BaseResponse::error('NotificationTemplate Delete Exception: ' . $exception->getMessage());
        }
    }
}