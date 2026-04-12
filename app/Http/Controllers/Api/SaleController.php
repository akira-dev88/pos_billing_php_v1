<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Models\StockLedger;
use App\Services\SaleService;
use App\Models\Setting;

use App\Helpers\ResponseHelper;

class SaleController extends Controller
{
    protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
        ]);

        $result = $this->saleService->createSale(
            $request->items,
            app('tenant_uuid')
        );

        return ResponseHelper::success($result);
    }

    public function checkout(Request $request, $cart_uuid)
    {
        $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0',
            'customer_uuid' => 'nullable|uuid'
        ]);

        try {
            $result = $this->saleService->checkoutCart(
                $cart_uuid,
                $request->payments,
                app('tenant_uuid'),
                $request->customer_uuid
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function invoice($sale_uuid)
    {

        $setting = Setting::where('tenant_uuid', app('tenant_uuid'))->first();

        $sale = Sale::where('sale_uuid', $sale_uuid)
            ->where('tenant_uuid', app('tenant_uuid'))
            ->with(['items.product', 'payments', 'customer'])
            ->firstOrFail();

        $items = [];
        $total = 0;
        $taxTotal = 0;

        foreach ($sale->items as $item) {

            $base = $item->price * $item->quantity;
            $tax = $item->tax_amount;

            $items[] = [
                'name' => $item->product->name,
                'qty' => $item->quantity,
                'price' => $item->price,
                'total' => $base,
                'tax_percent' => $item->tax_percent,
                'tax_amount' => $tax
            ];

            $total += $base;
            $taxTotal += $tax;
        }

        // GST Split (India)
        $cgst = $taxTotal / 2;
        $sgst = $taxTotal / 2;

        $payments = $sale->payments->map(function ($p) {
            return [
                'method' => $p->method,
                'amount' => $p->amount
            ];
        });

        return response()->json([

            'shop' => $setting ? [
                'name' => $setting->shop_name,
                'mobile' => $setting->mobile,
                'address' => $setting->address,
                'gstin' => $setting->gstin,
            ] : null,

            'invoice_number' => $sale->invoice_number,
            'date' => $sale->created_at,

            'customer' => $sale->customer ? [
                'name' => $sale->customer->name,
                'mobile' => $sale->customer->mobile
            ] : null,

            'items' => $items,

            'summary' => [
                'total' => $total,
                'tax' => $taxTotal,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'grand_total' => $sale->grand_total
            ],

            'payments' => $payments
        ]);
    }
}
