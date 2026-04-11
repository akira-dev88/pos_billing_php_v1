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

    // 🔍 Search products (for typing)
    public function search(Request $request)
    {
        $query = $request->query('q');

        $products = Product::where('tenant_uuid', app('tenant_uuid'))
            ->where('name', 'LIKE', "%{$query}%")
            ->limit(20) // 🔥 important for speed
            ->get();

        return response()->json($products);
    }

    // 📦 Barcode scan
    public function findByBarcode($barcode)
    {
        $product = Product::where('tenant_uuid', app('tenant_uuid'))
            ->where('barcode', $barcode)
            ->firstOrFail();

        return response()->json($product);
    }

    // 🏷 SKU lookup
    public function findBySku($sku)
    {
        $product = Product::where('tenant_uuid', app('tenant_uuid'))
            ->where('sku', $sku)
            ->firstOrFail();

        return response()->json($product);
    }
}
