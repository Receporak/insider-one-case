<?php

namespace App\Http\Controllers;

use App\Services\Metric\MetricService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MetricController extends Controller
{
    public function __construct(private readonly MetricService $service) {}

    /**
     * @OA\Get(
     *     path="/metrics/notifications",
     *     tags={"Metrics"},
     *     summary="Notification metrikleri",
     *     description="Queue depth, success/failure rates ve ortalama latency bilgisini döner.",
     *     operationId="metrics.notifications",
     *     @OA\Response(
     *         response=200,
     *         description="Metrik verisi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="healthy"),
     *             @OA\Property(property="timestamp", type="string", example="2026-03-06T12:00:00Z"),
     *             @OA\Property(property="services", type="object",
     *             @OA\Property(property="queue_depth", type="object",
     *                 @OA\Property(property="high",    type="integer", example=5),
     *                 @OA\Property(property="default", type="integer", example=12),
     *                 @OA\Property(property="low",     type="integer", example=3),
     *                 @OA\Property(property="total",   type="integer", example=20)
     *             ),
     *             @OA\Property(property="rates", type="object",
     *                 @OA\Property(property="sent",             type="integer", example=150),
     *                 @OA\Property(property="failed",           type="integer", example=10),
     *                 @OA\Property(property="pending",          type="integer", example=20),
     *                 @OA\Property(property="total",            type="integer", example=180),
     *                 @OA\Property(property="success_rate_pct", type="number",  example=93.75)
     *             ),
     *             @OA\Property(property="latency", type="object",
     *                 @OA\Property(property="avg_seconds",  type="number",  example=2.45),
     *                 @OA\Property(property="min_seconds",  type="number",  example=0.12),
     *                 @OA\Property(property="max_seconds",  type="number",  example=15.3),
     *                 @OA\Property(property="count", type="integer", example=150)
     *             ) 
     *         )
     *     )
     *    )
     * )
     */
    public function getNotificationMetric(): JsonResponse
    {
        try {
            return response()->json($this->service->getNotificationMetrics()->toArray());
        } catch (Exception $exception) {
            Log::error('MetricController@getNotificationMetric Exception:', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            ]);

            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}