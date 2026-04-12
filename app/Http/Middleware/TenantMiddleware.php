<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use App\Helpers\ResponseHelper;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $tenant = \App\Models\Tenant::where('tenant_uuid', $user->tenant_uuid)->first();

        if (!$tenant) {
            return \App\Helpers\ResponseHelper::error('Tenant not found', 404);
        }

        if (!$tenant->is_active) {
            return ResponseHelper::error('Account inactive', 403);
        }

        if (!$tenant->expiry_date) {
            return ResponseHelper::error('No active plan', 403);
        }

        if (Carbon::now()->gte($tenant->expiry_date)) {
            return ResponseHelper::error('Subscription expired', 403);
        }

        if ($tenant->expiry_date && Carbon::now()->gte($tenant->expiry_date)) {
            return \App\Helpers\ResponseHelper::error('Subscription expired', 403);
        }


        app()->instance('tenant_uuid', $tenant->tenant_uuid);

        return $next($request);
    }
}
