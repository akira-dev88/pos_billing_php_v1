<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Setting;

class SaleService
{
    // ✅ DIRECT SALE (old API support)
    public function createSale(array $items, string $tenantUuid, ?string $customerUuid = null)
    {
        return DB::transaction(function () use ($items, $tenantUuid, $customerUuid) {

            $total = 0;
            $taxTotal = 0;
            $itemsData = [];
            $setting = Setting::where('tenant_uuid', $tenantUuid)->first();
            $prefix = $setting->invoice_prefix ?? 'INV';

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

            $lastInvoice = Sale::where('tenant_uuid', $tenantUuid)->latest()->first();

            $nextNumber = $lastInvoice
                ? ((int) str_replace('INV-', '', $lastInvoice->invoice_number)) + 1
                : 1;

            $invoiceNumber = $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'tenant_uuid' => $tenantUuid,
                'invoice_number' => $invoiceNumber,
                'total' => $total,
                'tax' => $taxTotal,
                'grand_total' => $grandTotal,
                'status' => 'completed',
            ]);

            if ($customerUuid) {
                CustomerLedger::create([
                    'tenant_uuid' => $tenantUuid,
                    'customer_uuid' => $customerUuid,
                    'type' => 'sale',
                    'amount' => $grandTotal,
                    'reference_uuid' => $sale->sale_uuid,
                    'note' => 'Sale created',
                ]);
            }

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
                    'note' => 'Direct sale',
                ]);
            }

            return [
                'sale' => $sale,
                'items' => $itemsData
            ];
        });
    }

    // ✅ CART CHECKOUT (new flow)
    public function checkoutCart(string $cartUuid, array $payments, string $tenantUuid, ?string $customerUuid = null)
    {
        return DB::transaction(function () use ($cartUuid, $payments, $tenantUuid, $customerUuid) {

            $cart = Cart::where('cart_uuid', $cartUuid)
                ->where('tenant_uuid', $tenantUuid)
                ->with('items.product')
                ->firstOrFail();

            if ($cart->status === 'completed') {
                throw new \Exception("Cart already completed");
            }

            $total = 0;
            $taxTotal = 0;

            foreach ($cart->items as $item) {

                $product = Product::where('product_uuid', $item->product_uuid)
                    ->where('tenant_uuid', $tenantUuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->stock < $item->quantity) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                $itemTotal = $item->price * $item->quantity;
                $taxAmount = ($itemTotal * $item->tax_percent) / 100;

                $total += $itemTotal;
                $taxTotal += $taxAmount;

                $product->decrement('stock', $item->quantity);
            }

            $grandTotal = $total + $taxTotal;

            $lastInvoice = Sale::where('tenant_uuid', $tenantUuid)->latest()->first();

            $nextNumber = $lastInvoice
                ? ((int) str_replace('INV-', '', $lastInvoice->invoice_number)) + 1
                : 1;

            $invoiceNumber = 'INV-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'tenant_uuid' => $tenantUuid,
                'customer_uuid' => $customerUuid,
                'invoice_number' => $invoiceNumber,
                'total' => $total,
                'tax' => $taxTotal,
                'grand_total' => $grandTotal,
                'status' => 'completed',
            ]);

            foreach ($cart->items as $item) {

                SaleItem::create([
                    'sale_uuid' => $sale->sale_uuid,
                    'product_uuid' => $item->product_uuid,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'tax_percent' => $item->tax_percent,
                    'tax_amount' => ($item->price * $item->quantity * $item->tax_percent) / 100,
                ]);

                StockLedger::create([
                    'tenant_uuid' => $tenantUuid,
                    'product_uuid' => $item->product_uuid,
                    'quantity' => -$item->quantity,
                    'type' => 'sale',
                    'reference_uuid' => $sale->sale_uuid,
                    'note' => 'Sale via cart checkout',
                ]);
            }

            $paidAmount = 0;

            foreach ($payments as $p) {

                Payment::create([
                    'tenant_uuid' => $tenantUuid,
                    'sale_uuid' => $sale->sale_uuid,
                    'method' => $p['method'],
                    'amount' => $p['amount'],
                    'reference' => $p['reference'] ?? null,
                ]);

                $paidAmount += $p['amount'];
            }

            $balance = $grandTotal - $paidAmount;

            // 🧾 Update customer credit
            if ($customerUuid && $balance > 0) {

                $customer = Customer::where('customer_uuid', $customerUuid)
                    ->where('tenant_uuid', $tenantUuid)
                    ->first();

                if ($customer) {
                    $customer->increment('credit_balance', $balance);
                }
            }

            $cart->update(['status' => 'completed']);

            return [
                'sale' => $sale,
                'paid' => $paidAmount,
                'balance' => $grandTotal - $paidAmount
            ];
        });
    }
}
