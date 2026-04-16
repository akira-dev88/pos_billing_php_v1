<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * 🔥 Always get tenant from authenticated user
     */
    private function getTenant()
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        return $user->tenant_uuid;
    }

    /**
     * GET settings
     */
    public function get()
    {
        $tenant_uuid = $this->getTenant();

        $setting = Setting::where('tenant_uuid', $tenant_uuid)->first();

        return response()->json([
            'success' => true,
            'data' => $setting ?? new Setting()
        ]);
    }

    /**
     * CREATE or UPDATE settings
     */
    public function save(Request $request)
    {
        $tenant_uuid = $this->getTenant();

        $request->validate([
            'shop_name' => 'required|string',
        ]);

        $setting = Setting::updateOrCreate(
            ['tenant_uuid' => $tenant_uuid],
            [
                'shop_name' => $request->shop_name,
                'mobile' => $request->mobile,
                'address' => $request->address,
                'gstin' => $request->gstin,
                'invoice_prefix' => $request->invoice_prefix ?? 'INV',
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    /**
     * OPTIONAL explicit update
     */
    public function update(Request $request)
    {
        $tenant_uuid = $this->getTenant();

        $setting = Setting::where('tenant_uuid', $tenant_uuid)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found'
            ], 404);
        }

        $setting->update($request->only([
            'shop_name',
            'mobile',
            'address',
            'gstin',
            'invoice_prefix'
        ]));

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }
}