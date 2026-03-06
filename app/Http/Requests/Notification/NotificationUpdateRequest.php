<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class NotificationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient' => ['nullable', 'string'],
            'channel'   => ['nullable', 'string', 'in:sms,email,push'],
            'template_id'   => ['nullable', 'uuid'],
            'content'   => ['nullable', 'string'],
            'priority'  => ['nullable', 'string', 'in:low,normal,high']
        ];
    }

    public function messages(): array
    {
        return [
            'recipient.string'   => 'recipient bir metin olmalıdır.',
            'channel.in'         => 'channel yalnızca sms, email veya push olabilir.',
            'template_id.uuid'     => 'template_id uuid olmalıdır.',
            'priority.in'        => 'priority yalnızca low, normal veya high olabilir.'
        ];
    }
}