<?php

namespace App\Http\Controllers\Notification;

use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\NotificationRequest;
use App\Http\Requests\Notification\NotificationUpdateRequest;
use App\Jobs\NotificationQueueManagement;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $service)
    {
    }

    /**
     * @OA\Post(
     *     path="/notification",
     *     tags={"Notifications"},
     *     summary="Toplu bildirim oluştur",
     *     description="Bir veya birden fazla bildirimi aynı anda oluşturur ve kuyruğa ekler.",
     *     operationId="notification.insert",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Bildirim listesi",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/NotificationItem"),
     *             example={
     *                 {"recipient": "905321234567", "channel": "sms", "template_id": "123e4567-e89b-12d3-a456-426614174000", "priority": "high"},
     *                 {"recipient": "user@example.com", "channel": "email", "template_id": "123e4567-e89b-12d3-a456-426614174000", "priority": "normal"}
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bildirimler başarıyla oluşturuldu",
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
    public function insert(NotificationRequest $request): JsonResponse
    {
        $data = $request->validated();
        NotificationQueueManagement::dispatch($data);

        return response()->json(BaseResponse::success(null, 'Successfully', 201)->toArray(), 201);
    }

    /**
     * @OA\Get(
     *     path="/notification/{recipient}",
     *     tags={"Notifications"},
     *     summary="Alıcıya göre bildirim getir",
     *     description="Belirtilen alıcıya ait bildirimleri döner.",
     *     operationId="notification.findByRecipient",
     *     @OA\Parameter(
     *         name="recipient",
     *         in="path",
     *         required=true,
     *         description="Alıcı değeri (telefon, e-posta veya device token)",
     *         @OA\Schema(type="string", example="905321234567")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bildirim bulundu",
     *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bildirim bulunamadı veya sunucu hatası",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function findByRecipient(string $recipient): JsonResponse
    {
        $response = $this->service->findByRecipient($recipient);

        return response()->json($response->toArray(), $response->code);
    }

    /**
     * @OA\Get(
     *     path="/notification/{id}",
     *     tags={"Notifications"},
     *     summary="ID'ye göre bildirim getir",
     *     operationId="notification.findById",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Bildirim", @OA\JsonContent(ref="#/components/schemas/BaseResponse")),
     *     @OA\Response(response=404, description="Bulunamadı", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function findById(string $id): JsonResponse
    {
        $response = $this->service->findById($id);

        return response()->json($response->toArray(), $response->code);
    }

    /**
     * @OA\Get(
     *     path="/notification/findByBatchId/{batchId}",
     *     tags={"Notifications"},
     *     summary="Batch ID'ye göre bildirimleri getir",
     *     operationId="notification.findByBatchId",
     *     @OA\Parameter(name="batchId", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Bildirim listesi", @OA\JsonContent(ref="#/components/schemas/BaseResponse"))
     * )
     */
    public function findByBatchId(string $batchId): JsonResponse
    {
        $response = $this->service->findByBatchId($batchId);

        return response()->json($response->toArray(), $response->code);
    }

    /**
     * @OA\Get(
     *     path="/notification",
     *     tags={"Notifications"},
     *     summary="Bildirim listesi",
     *     description="Bildirim listesini döner.",
     *     operationId="notification.list",
     *     @OA\Parameter(
     *         name="channel",
     *         in="query",
     *         required=false,
     *         description="Kanal",
     *         @OA\Schema(type="string", example="sms")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Durum",
     *         @OA\Schema(type="string", example="pending")
     *     ),
     *     @OA\Parameter(
     *         name="batch_id",
     *         in="query",
     *         required=false,
     *         description="Batch ID",
     *         @OA\Schema(type="string", example="123e4567-e89b-12d3-a456-426614174000")
     *     ),
     *     @OA\Parameter(
     *         name="first_date",
     *         in="query",
     *         required=false,
     *         description="İlk tarih",
     *         @OA\Schema(type="string", example="2022-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="last_date",
     *         in="query",
     *         required=false,
     *         description="Son tarih",
     *         @OA\Schema(type="string", example="2022-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="item_per_page",
     *         in="query",
     *         required=false,
     *         description="Sayfa başına öğe sayısı",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Sayfa numarası",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bildirim listesi",
     *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Sunucu hatası",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function list(Request $request): JsonResponse
    {
        $response = $this->service->list($request->all());

        return response()->json($response->toArray(), $response->code);
    }

    /**
     * @OA\Put(
     *     path="/notification/{id}",
     *     tags={"Notifications"},
     *     summary="Alıcıya ait bildirimi güncelle",
     *     description="Belirtilen alıcıya ait bildirim kayıtlarını günceller.",
     *     operationId="notification.update",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Bildirim ID",
     *         @OA\Schema(type="string", example="7bb61a62-1263-406a-b20d-081aff51ad5d")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="recipient", type="string", example="905321234567"),
     *             @OA\Property(property="channel", type="string", example="sms"),
     *             @OA\Property(property="status", type="string", example="sent"),
     *             @OA\Property(property="batch_id", type="string", example="123e4567-e89b-12d3-a456-426614174000")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bildirim güncellendi",
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
    public function update(string $id, NotificationUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $response = $this->service->update($id, $data);

        return response()->json($response->toArray(), $response->code);
    }

    

    /**
     * @OA\Put(
     *     path="/notification/updateByBatchId/{batchId}",
     *     tags={"Notifications"},
     *     summary="Alıcıya ait bildirimi güncelle",
     *     description="Belirtilen alıcıya ait bildirim kayıtlarını günceller.",
     *     operationId="notification.updateByBatchId",
     *     @OA\Parameter(
     *         name="batchId",
     *         in="path",
     *         required=true,
     *         description="Bildirim Batch ID",
     *         @OA\Schema(type="string", example="7bb61a62-1263-406a-b20d-081aff51ad5d")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="sent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bildirim güncellendi",
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
    public function updateByBatchId(string $id, NotificationUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $response = $this->service->update($id, $data);

        return response()->json($response->toArray(), $response->code);
    }

    

    /**
     * @OA\Delete(
     *     path="/notification/{id}",
     *     tags={"Notifications"},
     *     summary="Alıcıya ait bildirimi sil",
     *     description="Belirtilen alıcıya ait tüm bildirim kayıtlarını siler.",
     *     operationId="notification.delete",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Bildirim ID",
     *         @OA\Schema(type="string", example="123e4567-e89b-12d3-a456-426614174000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bildirim silindi",
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