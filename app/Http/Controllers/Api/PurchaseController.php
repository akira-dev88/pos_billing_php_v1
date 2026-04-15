<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;

use App\Helpers\ResponseHelper;

class PurchaseController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'supplier_uuid' => 'nullable|exists:suppliers,supplier_uuid',
            'items' => 'required|array|min:1',
            'items.*.product_uuid' => 'required|exists:products,product_uuid',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {

            $total = 0;

            $purchase = Purchase::create([
                'tenant_uuid' => app('tenant_uuid'),
                'supplier_uuid' => $request->supplier_uuid,
                'total' => 0,
            ]);

            foreach ($request->items as $item) {

                $product = Product::where('product_uuid', $item['product_uuid'])
                    ->where('tenant_uuid', app('tenant_uuid'))
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity = $item['quantity'];
                $costPrice = $item['cost_price'];

                $product->increment('stock', $quantity);

                PurchaseItem::create([
                    'purchase_uuid' => $purchase->purchase_uuid,
                    'product_uuid' => $product->product_uuid,
                    'quantity' => $quantity,
                    'cost_price' => $costPrice,
                ]);

                StockLedger::create([
                    'tenant_uuid' => app('tenant_uuid'),
                    'product_uuid' => $product->product_uuid,
                    'quantity' => $quantity,
                    'type' => 'purchase',
                    'reference_uuid' => $purchase->purchase_uuid,
                    'note' => 'Stock added via purchase',
                ]);

                $total += $quantity * $costPrice;
            }

            $purchase->update(['total' => $total]);

            return ResponseHelper::success($purchase, 'Purchase created');
        });
    }

    // 📋 List purchases
    public function index()
    {
        $purchases = Purchase::where('tenant_uuid', app('tenant_uuid'))
            ->with([
                'items.product',
                'supplier' // ✅ include supplier
            ])
            ->latest()
            ->get();

        return response()->json($purchases);
    }
}
