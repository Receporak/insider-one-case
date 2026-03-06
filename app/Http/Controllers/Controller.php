<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Insider One - Notification API",
 *     version="1.0.0",
 *     description="Toplu bildirim ve şablon yönetimi için REST API dokümantasyonu.",
 *     @OA\Contact(
 *         email="dev@insider.com",
 *         name="Insider Dev Team"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="Local Development Server"
 * )
 *
 * @OA\Tag(
 *     name="Notifications",
 *     description="Toplu bildirim gönderme ve yönetme işlemleri"
 * )
 *
 * @OA\Tag(
 *     name="Notification Templates",
 *     description="Bildirim şablonu oluşturma ve yönetme işlemleri"
 * )
 *
 * @OA\Schema(
 *     schema="NotificationItem",
 *     required={"recipient", "channel", "content", "priority"},
 *     @OA\Property(property="recipient", type="string", example="905321234567", description="Alıcı (telefon, e-posta veya device token)"),
 *     @OA\Property(property="channel", type="string", enum={"sms", "email", "push"}, example="sms", description="Bildirim kanalı"),
 *     @OA\Property(property="content", type="string", example="Merhaba! Siparişiniz kargoya verildi.", description="Bildirim içeriği"),
 *     @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="high", description="Gönderim önceliği")
 * )
 *
 * @OA\Schema(
 *     schema="NotificationTemplateItem",
 *     required={"name", "channel", "content"},
 *     @OA\Property(property="name", type="string", example="order_shipped", description="Şablon adı"),
 *     @OA\Property(property="channel", type="string", enum={"sms", "email", "push"}, example="email", description="Kanal"),
 *     @OA\Property(property="content", type="string", example="Merhaba, siparişiniz kargoya verilmiştir.", description="Şablon içeriği"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active", description="Şablon durumu")
 * )
 *
 * @OA\Schema(
 *     schema="BaseResponse",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", nullable=true),
 *     @OA\Property(property="code", type="integer", example=200)
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(property="data", nullable=true, example=null),
 *     @OA\Property(property="code", type="integer", example=400)
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\AdditionalProperties(
 *             type="array",
 *             @OA\Items(type="string")
 *         )
 *     )
 * )
 */
abstract class Controller
{

}
