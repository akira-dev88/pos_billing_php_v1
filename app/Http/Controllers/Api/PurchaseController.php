<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
        ]);

        DB::beginTransaction();

        try {

            $total = 0;

            $purchase = Purchase::create([
                'tenant_uuid' => app('tenant_uuid'),
                'supplier_name' => $request->supplier_name,
                'total' => 0,
            ]);

            foreach ($request->items as $item) {

                $product = Product::where('product_uuid', $item['product_uuid'])
                    ->where('tenant_uuid', app('tenant_uuid'))
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity = $item['quantity'];
                $costPrice = $item['cost_price'];

                // ✅ Increase stock
                $product->stock += $quantity;
                $product->save();

                // ✅ Purchase item
                PurchaseItem::create([
                    'purchase_uuid' => $purchase->purchase_uuid,
                    'product_uuid' => $product->product_uuid,
                    'quantity' => $quantity,
                    'cost_price' => $costPrice,
                ]);

                // ✅ Ledger entry
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

            DB::commit();

            return response()->json([
                'purchase' => $purchase
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}