<?php

namespace App\Models;

use App\Enums\Notifications\NotificationChannel;
use App\Enums\Notifications\NotificationPriority;
use App\Enums\Notifications\NotificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'recipient',
        'channel',
        'template_id',
        'content',
        'priority',
        'status',
        'batch_id'
    ];

    protected $casts = [
        'channel'  => NotificationChannel::class,
        'priority' => NotificationPriority::class,
        'status'   => NotificationStatus::class,
    ];
}
