<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\suppliers;

class SupplierController extends Controller
{
    // 📋 List
    public function index()
    {
        return response()->json(
            Suppliers::where('tenant_uuid', app('tenant_uuid'))->latest()->get()
        );
    }

    // ➕ Create
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
        ]);

        $supplier = Suppliers::create([
            'tenant_uuid' => app('tenant_uuid'),
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
        ]);

        return response()->json($supplier);
    }

    // ✏️ Update
    public function update(Request $request, $supplier_uuid)
    {
        $supplier = Suppliers::where('supplier_uuid', $supplier_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->firstOrFail();

        $supplier->update($request->only([
            'name',
            'phone',
            'email',
            'address',
        ]));

        return response()->json($supplier);
    }

    // ❌ Delete
    public function destroy($supplier_uuid)
    {
        $supplier = Suppliers::where('supplier_uuid', $supplier_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->firstOrFail();

        $supplier->delete();

        return response()->json(['message' => 'Deleted']);
    }
}