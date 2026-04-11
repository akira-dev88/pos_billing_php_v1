<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // 📊 Dashboard summary
    public function dashboard()
    {
        $tenant = app('tenant_uuid');

        $today = now()->startOfDay();
        $month = now()->startOfMonth();

        $todaySales = Sale::where('tenant_uuid', $tenant)
            ->where('created_at', '>=', $today)
            ->sum('grand_total');

        $monthSales = Sale::where('tenant_uuid', $tenant)
            ->where('created_at', '>=', $month)
            ->sum('grand_total');

        return response()->json([
            'today_sales' => $todaySales,
            'month_sales' => $monthSales
        ]);
    }

    // 🏆 Top products
    public function topProducts()
    {
        $tenant = app('tenant_uuid');

        $products = SaleItem::select('product_uuid', DB::raw('SUM(quantity) as total_qty'))
            ->join('sales', 'sales.sale_uuid', '=', 'sale_items.sale_uuid')
            ->where('sales.tenant_uuid', $tenant)
            ->groupBy('product_uuid')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->with('product')
            ->get();

        return response()->json($products);
    }

    // 📦 Stock report
    public function stock()
    {
        return Product::where('tenant_uuid', app('tenant_uuid'))
            ->get(['name', 'stock', 'price']);
    }

    // 💰 Profit estimation
    public function profit()
    {
        $tenant = app('tenant_uuid');

        $sales = SaleItem::join('sales', 'sales.sale_uuid', '=', 'sale_items.sale_uuid')
            ->where('sales.tenant_uuid', $tenant)
            ->sum(DB::raw('sale_items.price * sale_items.quantity'));

        $purchases = PurchaseItem::join('purchases', 'purchases.purchase_uuid', '=', 'purchase_items.purchase_uuid')
            ->where('purchases.tenant_uuid', $tenant)
            ->sum(DB::raw('purchase_items.cost_price * purchase_items.quantity'));

        return response()->json([
            'revenue' => $sales,
            'cost' => $purchases,
            'profit' => $sales - $purchases
        ]);
    }
}
