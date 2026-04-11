<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'mobile' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'tenant_uuid' => app('tenant_uuid'),
            'name' => $request->name,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'gstin' => $request->gstin,
        ]);

        return response()->json($customer);
    }

    public function index()
    {
        return Customer::where('tenant_uuid', app('tenant_uuid'))
            ->latest()
            ->get();
    }
}
