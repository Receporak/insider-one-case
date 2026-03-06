<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*'           => ['required', 'array'],
            '*.recipient' => ['required', 'string'],
            '*.channel'   => ['required', 'string', 'in:sms,email,push'],
            '*.template_id'   => ['nullable', 'uuid'],
            '*.content'   => ['nullable', 'string'],
            '*.priority'  => ['required', 'string', 'in:low,normal,high'],
            '*.batch_id'  => ['nullable', 'uuid']
        ];
    }

    public function messages(): array
    {
        return [
            '*.required'           => 'Her eleman bir dizi (array) olmalıdır.',
            '*.recipient.required' => 'recipient alanı zorunludur.',
            '*.recipient.string'   => 'recipient bir metin olmalıdır.',
            '*.channel.required'   => 'channel alanı zorunludur.',
            '*.channel.in'         => 'channel yalnızca sms, email veya push olabilir.',
            '*.template_id.uuid'     => 'template_id uuid olmalıdır.',
            '*.priority.required'  => 'priority alanı zorunludur.',
            '*.priority.in'        => 'priority yalnızca low, normal veya high olabilir.',
            '*.batch_id.uuid'      => 'batch_id uuid olmalıdır.'
        ];
    }
}