<?php

namespace App\Helpers\Notification;

class TemplateHelper
{
    /**
     * Template içeriğindeki {{variable}} yer tutucularını verilen data ile değiştirir.
     * Eşleşmeyen değişkenler olduğu gibi bırakılır.
     */
    public static function render(string $content, array $data): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function (array $matches) use ($data): string {
            return array_key_exists($matches[1], $data)
                ? (string) $data[$matches[1]]
                : $matches[0];
        }, $content);
    }
}
