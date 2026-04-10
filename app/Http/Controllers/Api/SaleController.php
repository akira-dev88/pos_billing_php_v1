<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Models\StockLedger;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
        ]);

        DB::beginTransaction();

        try {

            $total = 0;
            $taxTotal = 0;

            $itemsData = [];

            foreach ($request->items as $item) {

                $product = Product::where('product_uuid', $item['product_uuid'])
                    ->where('tenant_uuid', app('tenant_uuid'))
                    ->lockForUpdate() // 🔥 prevents race conditions
                    ->firstOrFail();

                $quantity = $item['quantity'];

                // ❗ Check stock
                if ($product->stock < $quantity) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                $price = $product->price;
                $taxPercent = $product->gst_percent;

                $itemTotal = $price * $quantity;
                $taxAmount = ($itemTotal * $taxPercent) / 100;

                $total += $itemTotal;
                $taxTotal += $taxAmount;

                // ✅ Reduce stock
                $product->stock -= $quantity;
                $product->save();

                $itemsData[] = [
                    'product_uuid' => $product->product_uuid,
                    'quantity' => $quantity,
                    'price' => $price,
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $taxAmount,
                ];

                // ✅ Reduce stock
                $product->stock -= $quantity;
                $product->save();

                // ✅ Add ledger entry
                StockLedger::create([
                    'tenant_uuid' => app('tenant_uuid'),
                    'product_uuid' => $product->product_uuid,
                    'quantity' => -$quantity,
                    'type' => 'sale',
                    'reference_uuid' => null, // we’ll improve later
                    'note' => 'Sale transaction',
                ]);
            }

            $grandTotal = $total + $taxTotal;

            $sale = Sale::create([
                'tenant_uuid' => app('tenant_uuid'),
                'total' => $total,
                'tax' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);

            foreach ($itemsData as $data) {
                SaleItem::create([
                    'sale_uuid' => $sale->sale_uuid,
                    ...$data
                ]);
            }

            DB::commit();

            return response()->json([
                'sale' => $sale,
                'items' => $itemsData
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
