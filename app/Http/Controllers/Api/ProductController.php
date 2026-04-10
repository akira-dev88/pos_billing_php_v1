<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    // ✅ Create Product
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
        ]);

        $product = Product::create([
            'tenant_uuid' => app('tenant_uuid'),
            'name' => $request->name,
            'barcode' => $request->barcode,
            'sku' => $request->sku,
            'price' => $request->price,
            'gst_percent' => $request->gst_percent ?? 0,
            'stock' => $request->stock ?? 0,
        ]);

        return response()->json($product);
    }

    // ✅ List Products
    public function index()
    {
        $products = Product::where('tenant_uuid', app('tenant_uuid'))
            ->latest()
            ->get();

        return response()->json($products);
    }
}