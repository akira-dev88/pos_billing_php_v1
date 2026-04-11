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
        $itemDiscountTotal = 0;

        foreach ($cart->items as $item) {

            $itemBase = $item->price * $item->quantity;
            $itemDiscount = $item->discount ?? 0;

            $itemNet = $itemBase - $itemDiscount;

            $taxAmount = ($itemNet * $item->tax_percent) / 100;

            $total += $itemBase;
            $itemDiscountTotal += $itemDiscount;
            $taxTotal += $taxAmount;
        }

        $itemDiscountTotal = $cart->items->sum('discount');
        $billDiscount = $cart->discount ?? 0;

        $grandTotal = $total - $itemDiscountTotal - $billDiscount + $taxTotal;

        return response()->json([
            'cart' => $cart,
            'summary' => [
                'total' => $total,
                'item_discount' => $itemDiscountTotal,
                'bill_discount' => $billDiscount,
                'tax' => $taxTotal,
                'grand_total' => $grandTotal
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

    public function updateItem(Request $request, $cart_uuid, $product_uuid)
    {
        $item = CartItem::where('cart_uuid', $cart_uuid)
            ->where('product_uuid', $product_uuid)
            ->firstOrFail();

        if ($request->has('quantity')) {
            $item->quantity = $request->quantity;
        }

        if ($request->has('price')) {
            $item->price = $request->price; // 🔥 price override
        }

        if ($request->has('discount')) {
            $item->discount = $request->discount;
        }

        $item->save();

        return response()->json($item);
    }

    public function applyDiscount(Request $request, $cart_uuid)
    {
        $request->validate([
            'discount' => 'required|numeric|min:0'
        ]);

        $cart = Cart::where('cart_uuid', $cart_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->firstOrFail();

        $cart->update([
            'discount' => $request->discount
        ]);

        return response()->json(['message' => 'Discount applied']);
    }
}
