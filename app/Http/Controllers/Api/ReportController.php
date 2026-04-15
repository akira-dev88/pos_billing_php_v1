<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Purchase;

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

        // ✅ FIXED
        $totalSales = Sale::where('tenant_uuid', $tenant)
            ->sum('grand_total');

        $totalOrders = Sale::where('tenant_uuid', $tenant)
            ->count();

        $recentSales = Sale::where('tenant_uuid', $tenant)
            ->latest()
            ->take(5)
            ->get();

        $lowStock = Product::where('tenant_uuid', $tenant)
            ->where('stock', '<', 10)
            ->get();

        $topProducts = SaleItem::select('product_uuid', DB::raw('SUM(quantity) as total_qty'))
            ->join('sales', 'sales.sale_uuid', '=', 'sale_items.sale_uuid')
            ->where('sales.tenant_uuid', $tenant)
            ->groupBy('product_uuid')
            ->with('product')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get()
            ->map(fn($i) => [
                'name' => $i->product->name ?? 'Unknown',
                'total_qty' => $i->total_qty
            ]);

        $recentPurchases = Purchase::with('supplier')
            ->where('tenant_uuid', $tenant)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'today_sales' => $todaySales,
            'month_sales' => $monthSales,
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'recent_sales' => $recentSales,
            'low_stock' => $lowStock,
            'top_products' => $topProducts,
            'recent_purchases' => $recentPurchases,
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
        try {
            return Product::where('tenant_uuid', app('tenant_uuid'))
                ->get(['name', 'stock', 'price']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 💰 Profit estimation
    public function profit()
    {
        $tenant = app('tenant_uuid');

        $sales = SaleItem::join('sales', 'sales.sale_uuid', '=', 'sale_items.sale_uuid')
            ->where('sales.tenant_uuid', $tenant)
            ->sum(DB::raw('sale_items.price * sale_items.quantity'));

        $purchases = 0;

        if (Schema::hasTable('purchase_items')) {
            $purchases = PurchaseItem::join('purchases', 'purchases.purchase_uuid', '=', 'purchase_items.purchase_uuid')
                ->where('purchases.tenant_uuid', $tenant)
                ->sum(DB::raw('purchase_items.cost_price * purchase_items.quantity'));
        }

        return response()->json([
            'revenue' => $sales ?? 0,
            'cost' => $purchases ?? 0,
            'profit' => ($sales ?? 0) - ($purchases ?? 0)
        ]);
    }

    public function salesTrend()
    {
        $tenant = app('tenant_uuid');

        $data = Sale::where('tenant_uuid', $tenant)
            ->where('created_at', '>=', now()->subDays(6)) // last 7 days
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(grand_total) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    public function profitTrend()
    {
        $tenant = app('tenant_uuid');

        // Sales per day
        $sales = SaleItem::join('sales', 'sales.sale_uuid', '=', 'sale_items.sale_uuid')
            ->where('sales.tenant_uuid', $tenant)
            ->where('sales.created_at', '>=', now()->subDays(6))
            ->select(
                DB::raw('DATE(sales.created_at) as date'),
                DB::raw('SUM(sale_items.price * sale_items.quantity) as revenue')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Purchases per day
        $purchases = PurchaseItem::join('purchases', 'purchases.purchase_uuid', '=', 'purchase_items.purchase_uuid')
            ->where('purchases.tenant_uuid', $tenant)
            ->where('purchases.created_at', '>=', now()->subDays(6))
            ->select(
                DB::raw('DATE(purchases.created_at) as date'),
                DB::raw('SUM(purchase_items.cost_price * purchase_items.quantity) as cost')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $result = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $revenue = $sales[$date]->revenue ?? 0;
            $cost = $purchases[$date]->cost ?? 0;

            $result[] = [
                'date' => $date,
                'revenue' => (float) $revenue,
                'cost' => (float) $cost,
                'profit' => (float) $revenue - (float) $cost,
            ];
        }

        return response()->json($result);
    }
}
