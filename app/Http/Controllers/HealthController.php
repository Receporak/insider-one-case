<?php

namespace App\Http\Controllers;

use App\Helpers\BaseResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/health",
     *     tags={"Health"},
     *     summary="Sistem sağlık durumu",
     *     description="Veritabanı, Redis ve Queue bağlantılarının durumunu döner.",
     *     operationId="health.check",
     *     @OA\Response(
     *         response=200,
     *         description="Tüm servisler sağlıklı",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="healthy"),
     *             @OA\Property(property="timestamp", type="string", example="2026-03-06T12:00:00Z"),
     *             @OA\Property(property="services", type="object",
     *                 @OA\Property(property="database", type="object",
     *                     @OA\Property(property="status", type="string", example="up"),
     *                     @OA\Property(property="message", type="string", example="OK")
     *                 ),
     *                 @OA\Property(property="redis", type="object",
     *                     @OA\Property(property="status", type="string", example="up"),
     *                     @OA\Property(property="message", type="string", example="OK")
     *                 ),
     *                 @OA\Property(property="queue", type="object",
     *                     @OA\Property(property="status", type="string", example="up"),
     *                     @OA\Property(property="message", type="string", example="OK")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Bir veya daha fazla servis çalışmıyor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="unhealthy"),
     *             @OA\Property(property="timestamp", type="string", example="2026-03-06T12:00:00Z"),
     *             @OA\Property(property="services", type="object")
     *         )
     *     )
     * )
     */
    public function check(): JsonResponse
    {
        $services = [
            'database' => $this->checkDatabase(),
            'redis'    => $this->checkRedis(),
            'queue'    => $this->checkQueue(),
        ];

        $allHealthy = collect($services)->every(fn($s) => $s['status'] === 'up');

        $data = [
            'status'    => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'services'  => $services,
        ];

        if ($allHealthy) {
            $response = BaseResponse::success($data, 'All services are healthy');
        } else {
            $response = BaseResponse::error('One or more services are down', 503);
            $response->data = $data;
        }

        return response()->json($response->toArray(), $response->code);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::statement('SELECT 1');
            return ['status' => 'up', 'message' => 'OK'];
        } catch (Exception $e) {
            return ['status' => 'down', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['status' => 'up', 'message' => 'OK'];
        } catch (Exception $e) {
            return ['status' => 'down', 'message' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $size = Queue::size();
            return ['status' => 'up', 'message' => 'OK', 'pending_jobs' => $size];
        } catch (Exception $e) {
            return ['status' => 'down', 'message' => $e->getMessage()];
        }
    }
}
