<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // 🆕 Create new cart
    public function create()
    {
        $cart = Cart::create([
            'tenant_uuid' => app('tenant_uuid'),
            'status' => 'active',
        ]);

        return response()->json($cart);
    }

    // ➕ Add item
    public function addItem(Request $request, $cart_uuid)
    {
        $product = Product::where('product_uuid', $request->product_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->firstOrFail();

        $item = CartItem::updateOrCreate(
            [
                'cart_uuid' => $cart_uuid,
                'product_uuid' => $product->product_uuid
            ],
            [
                'quantity' => DB::raw('quantity + ' . $request->quantity),
                'price' => $product->price,
                'tax_percent' => $product->gst_percent,
            ]
        );

        return response()->json($item);
    }

    // 📥 Get cart
    public function show($cart_uuid)
    {
        $cart = Cart::where('cart_uuid', $cart_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->with('items.product')
            ->firstOrFail();

        return response()->json($cart);
    }

    // ⏸ Hold cart
    public function hold($cart_uuid)
    {
        $cart = Cart::where('cart_uuid', $cart_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->firstOrFail();

        $cart->update(['status' => 'held']);

        return response()->json(['message' => 'Cart held']);
    }

    // ▶ Resume cart
    public function resume($cart_uuid)
    {
        $cart = Cart::where('cart_uuid', $cart_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->firstOrFail();

        $cart->update(['status' => 'active']);

        return response()->json(['message' => 'Cart resumed']);
    }

    // 📋 List held carts
    public function heldCarts()
    {
        $carts = Cart::where('tenant_uuid', app('tenant_uuid'))
            ->where('status', 'held')
            ->latest()
            ->get();

        return response()->json($carts);
    }
}