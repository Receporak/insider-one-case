<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class NotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string'],
            'channel'   => ['required', 'string', 'in:sms,email,push'],
            'content'   => ['required', 'string'],
            'status'    => ['required', 'string', 'in:active,inactive']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'name alanı zorunludur.',
            'name.string'         => 'name bir metin olmalıdır.',
            'channel.required'   => 'channel alanı zorunludur.',
            'channel.in'         => 'channel yalnızca sms, email veya push olabilir.',
            'content.required'   => 'content alanı zorunludur.',
            'content.string'     => 'content bir metin olmalıdır.',
            'status.required'  => 'status alanı zorunludur.',
            'status.in'  => 'status yalnızca active veya inactive olabilir.'
        ];
    }
}