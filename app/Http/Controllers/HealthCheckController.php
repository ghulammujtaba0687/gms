<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class HealthCheckController extends Controller
{
    public function check(): JsonResponse
    {
        $dbStatus = 'ok';
        $storageStatus = 'writable';
        $isHealthy = true;

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $dbStatus = 'failed';
            $isHealthy = false;
        }

        try {
            $testFile = storage_path('app/health_test.txt');
            File::put($testFile, 'health check');
            File::delete($testFile);
        } catch (Throwable $e) {
            $storageStatus = 'failed';
            $isHealthy = false;
        }

        $statusCode = $isHealthy ? 200 : 503;

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => $dbStatus,
                'storage' => $storageStatus,
            ],
        ], $statusCode);
    }
}
