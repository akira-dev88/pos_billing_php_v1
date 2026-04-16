<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerLedger;

class CustomerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'mobile' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'tenant_uuid' => app('tenant_uuid'),
            'name' => $request->name,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'gstin' => $request->gstin,
            'credit_limit' => $request->credit_limit ?? 0,
        ]);

        return response()->json($customer);
    }

    public function index()
    {
        return Customer::where('tenant_uuid', app('tenant_uuid'))
            ->latest()
            ->get();
    }

    public function ledger($customer_uuid)
    {
        $entries = CustomerLedger::where('tenant_uuid', app('tenant_uuid'))
            ->where('customer_uuid', $customer_uuid)
            ->latest()
            ->get();

        $balance = 0;

        $ledger = $entries->map(function ($entry) use (&$balance) {
            $balance += $entry->type === 'debit'
                ? $entry->amount
                : -$entry->amount;

            return [
                ...$entry->toArray(),
                'balance' => $balance
            ];
        });

        return response()->json($ledger);
    }

    public function update(Request $request, $customer_uuid)
    {
        $customer = Customer::where('tenant_uuid', app('tenant_uuid'))
            ->where('customer_uuid', $customer_uuid)
            ->firstOrFail();

        $customer->update($request->only([
            'name',
            'mobile',
            'address',
            'gstin',
            'credit_limit', // ✅
        ]));

        return response()->json($customer);
    }

    public function destroy($customer_uuid)
    {
        $customer = Customer::where('tenant_uuid', app('tenant_uuid'))
            ->where('customer_uuid', $customer_uuid)
            ->firstOrFail();

        $customer->delete();

        return response()->json(['message' => 'Customer deleted']);
    }

    public function summary()
    {
        $tenant = app('tenant_uuid');

        $totalCredit = Customer::where('tenant_uuid', $tenant)
            ->sum('credit_balance');

        $customersWithCredit = Customer::where('tenant_uuid', $tenant)
            ->where('credit_balance', '>', 0)
            ->count();

        $topDebtors = Customer::where('tenant_uuid', $tenant)
            ->where('credit_balance', '>', 0)
            ->orderByDesc('credit_balance')
            ->take(5)
            ->get(['name', 'credit_balance']);

        return response()->json([
            'total_credit' => $totalCredit,
            'customers_with_credit' => $customersWithCredit,
            'top_debtors' => $topDebtors,
        ]);
    }

    public function aging()
    {
        $tenant = app('tenant_uuid');

        $customers = Customer::where('tenant_uuid', $tenant)
            ->where('credit_balance', '>', 0)
            ->get();

        $result = [];

        foreach ($customers as $c) {

            $ledger = \App\Models\CustomerLedger::where('customer_uuid', $c->customer_uuid)
                ->where('tenant_uuid', $tenant)
                ->where('type', 'sale')
                ->get();

            $aging = [
                '0_30' => 0,
                '31_60' => 0,
                '61_90' => 0,
                '90_plus' => 0,
            ];

            foreach ($ledger as $l) {
                $days = now()->diffInDays($l->created_at);

                if ($days <= 30) $aging['0_30'] += $l->amount;
                elseif ($days <= 60) $aging['31_60'] += $l->amount;
                elseif ($days <= 90) $aging['61_90'] += $l->amount;
                else $aging['90_plus'] += $l->amount;
            }

            $result[] = [
                'name' => $c->name,
                'credit_balance' => $c->credit_balance,
                'aging' => $aging,
            ];
        }

        return response()->json($result);
    }

    public function reminders()
    {
        $tenant = app('tenant_uuid');

        $customers = Customer::where('tenant_uuid', $tenant)
            ->where('credit_balance', '>', 0)
            ->get();

        $result = [];

        foreach ($customers as $c) {

            $lastPayment = \App\Models\CustomerLedger::where('customer_uuid', $c->customer_uuid)
                ->where('type', 'payment')
                ->latest()
                ->first();

            $days = $lastPayment
                ? now()->diffInDays($lastPayment->created_at)
                : 999;

            if ($days > 15) {
                $result[] = [
                    'name' => $c->name,
                    'mobile' => $c->mobile,
                    'due' => $c->credit_balance,
                    'days' => $days,
                ];
            }
        }

        return response()->json($result);
    }
}
