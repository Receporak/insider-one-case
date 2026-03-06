<?php

namespace App\Contracts;

use App\Helpers\BaseResponse;

interface NotificationChannelInterface
{
    /**
     * Bildirimi ilgili kanaldan gönderir.
     *
     * @param array{
     *     recipient: string,
     *     content: string|null,
     *     template_id: string|null,
     *     priority: string,
     *     status: string
     * } $notification
     */
    public function send(array $notification): BaseResponse;
}
