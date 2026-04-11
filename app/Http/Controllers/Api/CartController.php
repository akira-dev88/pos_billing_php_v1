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
        $request->validate([
            'product_uuid' => 'required|uuid',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::where('product_uuid', $request->product_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->firstOrFail();

        // 🔍 Check if item already exists
        $item = CartItem::where('cart_uuid', $cart_uuid)
            ->where('product_uuid', $product->product_uuid)
            ->first();

        if ($item) {
            // ✅ Increment properly
            $item->quantity += $request->quantity;
            $item->save();
        } else {
            // ✅ Create new
            $item = CartItem::create([
                'cart_uuid' => $cart_uuid,
                'product_uuid' => $product->product_uuid,
                'quantity' => $request->quantity,
                'price' => $product->price,
                'tax_percent' => $product->gst_percent,
            ]);
        }

        return response()->json($item);
    }

    // 📥 Get cart
    public function show($cart_uuid)
    {
        $cart = Cart::where('cart_uuid', $cart_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->with('items.product')
            ->firstOrFail();

        $total = 0;
        $taxTotal = 0;

        foreach ($cart->items as $item) {

            $itemTotal = $item->price * $item->quantity;
            $taxAmount = ($itemTotal * $item->tax_percent) / 100;

            $total += $itemTotal;
            $taxTotal += $taxAmount;
        }

        return response()->json([
            'cart' => $cart,
            'summary' => [
                'total' => $total,
                'tax' => $taxTotal,
                'grand_total' => $total + $taxTotal
            ]
        ]);
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
