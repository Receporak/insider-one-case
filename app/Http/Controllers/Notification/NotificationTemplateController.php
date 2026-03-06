<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\NotificationTemplateRequest;
use App\Services\Notification\NotificationTemplateService;
use Illuminate\Http\JsonResponse;


class NotificationTemplateController extends Controller
{
    public function __construct(private NotificationTemplateService $service)
    {
    }

    /**
     * @OA\Post(
     *     path="/notification-template",
     *     tags={"Notification Templates"},
     *     summary="Bildirim şablonu oluştur",
     *     description="Yeni bir bildirim şablonu oluşturur.",
     *     operationId="notificationTemplate.insert",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Şablon bilgileri",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="order_shipped"),
     *             @OA\Property(property="channel", type="string", example="sms"),
     *             @OA\Property(property="content", type="string", example="Siparişiniz kargoya verildi."),
     *             @OA\Property(property="status", type="string", example="active"),
     *             example={"name": "order_shipped", "channel": "sms", "content": "Siparişiniz kargoya verildi.", "status": "active"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Şablon başarıyla oluşturuldu",
     *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasyon hatası",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Sunucu hatası",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function create(NotificationTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $response = $this->service->create($data);

        return response()->json($response->toArray(), $response->code);
    }

    /**
     * @OA\Get(
     *     path="/notification-template/{id}",
     *     tags={"Notification Templates"},
     *     summary="ID ile şablon getir",
     *     description="UUID ile bir bildirim şablonunu getirir.",
     *     operationId="notificationTemplate.findById",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Şablon UUID'si",
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Şablon bulundu",
     *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Şablon bulunamadı veya sunucu hatası",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function findById(string $id): JsonResponse
    {
        $response = $this->service->findById($id);

        return response()->json($response->toArray(), $response->code);
    }

    /**
     * @OA\Put(
     *     path="/notification-template/{id}",
     *     tags={"Notification Templates"},
     *     summary="Bildirim şablonunu güncelle",
     *     description="Belirtilen UUID'ye sahip şablonu günceller.",
     *     operationId="notificationTemplate.update",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Şablon UUID'si",
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/NotificationTemplateItem")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Şablon güncellendi",
     *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasyon hatası",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Sunucu hatası",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(string $id, NotificationTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $response = $this->service->update($id, $data);

        return response()->json($response->toArray(), $response->code);
    }

    /**
     * @OA\Delete(
     *     path="/notification-template/{id}",
     *     tags={"Notification Templates"},
     *     summary="Bildirim şablonunu sil",
     *     description="Belirtilen UUID'ye sahip şablonu siler.",
     *     operationId="notificationTemplate.delete",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Şablon UUID'si",
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Şablon silindi",
     *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Sunucu hatası",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function delete(string $id): JsonResponse
    {
        $response = $this->service->delete($id);

        return response()->json($response->toArray(), $response->code);
    }
}