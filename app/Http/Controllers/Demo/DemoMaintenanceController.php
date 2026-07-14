<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Services\Demo\DemoCleanupService;
use Illuminate\Http\Request;

class DemoMaintenanceController extends Controller
{
    public function cleanup(Request $request, DemoCleanupService $cleanup)
    {
        $key = env('DEMO_CLEANUP_KEY');

        if (! $key || ! hash_equals($key, (string) $request->header('X-Demo-Cleanup-Key'))) {
            abort(404);
        }

        return response()->json([
            'status' => 'ok',
            'deleted' => $cleanup->cleanup($request->integer('ttl_minutes') ?: null),
        ]);
    }
}
