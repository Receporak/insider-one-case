<?php

namespace App\Models;

use App\Enums\Notifications\NotificationBatchStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NotificationBatches extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'status',
    ];

    protected $casts = [
        'status'   => NotificationBatchStatus::class,
    ];
}
