<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function createSale(array $items, string $tenantUuid)
    {
        return DB::transaction(function () use ($items, $tenantUuid) {

            $total = 0;
            $taxTotal = 0;
            $itemsData = [];

            foreach ($items as $item) {

                $product = Product::where('product_uuid', $item['product_uuid'])
                    ->where('tenant_uuid', $tenantUuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity = $item['quantity'];

                if ($product->stock < $quantity) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                $price = $product->price;
                $taxPercent = $product->gst_percent;

                $itemTotal = $price * $quantity;
                $taxAmount = ($itemTotal * $taxPercent) / 100;

                $total += $itemTotal;
                $taxTotal += $taxAmount;

                // Reduce stock
                $product->decrement('stock', $quantity);

                $itemsData[] = [
                    'product_uuid' => $product->product_uuid,
                    'quantity' => $quantity,
                    'price' => $price,
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $taxAmount,
                ];
            }

            $grandTotal = $total + $taxTotal;

            // Invoice generation
            $lastInvoice = Sale::where('tenant_uuid', $tenantUuid)
                ->latest()
                ->first();

            $nextNumber = $lastInvoice
                ? ((int) str_replace('INV-', '', $lastInvoice->invoice_number)) + 1
                : 1;

            $invoiceNumber = 'INV-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'tenant_uuid' => $tenantUuid,
                'invoice_number' => $invoiceNumber,
                'total' => $total,
                'tax' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);

            foreach ($itemsData as $data) {

                SaleItem::create([
                    'sale_uuid' => $sale->sale_uuid,
                    ...$data
                ]);

                StockLedger::create([
                    'tenant_uuid' => $tenantUuid,
                    'product_uuid' => $data['product_uuid'],
                    'quantity' => -$data['quantity'],
                    'type' => 'sale',
                    'reference_uuid' => $sale->sale_uuid,
                    'note' => 'Sale transaction',
                ]);
            }

            return [
                'sale' => $sale,
                'items' => $itemsData
            ];
        });
    }
}