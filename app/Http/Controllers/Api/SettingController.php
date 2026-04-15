<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Setting;

class SettingController extends Controller
{
    public function get()
    {
        $setting = Setting::where('tenant_uuid', app('tenant_uuid'))->first();

        return response()->json($setting ?? new Setting());
    }

    public function save(Request $request)
    {
        $request->validate([
            'shop_name' => 'required|string',
        ]);

        $setting = Setting::updateOrCreate(
            ['tenant_uuid' => app('tenant_uuid')],
            [
                'shop_name' => $request->shop_name,
                'mobile' => $request->mobile,
                'address' => $request->address,
                'gstin' => $request->gstin,
                'invoice_prefix' => $request->invoice_prefix ?? 'INV',
            ]
        );

        return response()->json($setting ?? new Setting());
    }
}
