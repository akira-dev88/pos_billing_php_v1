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

        $request->validate([
            'name' => 'required|string',
            'mobile' => 'nullable|string',
            'address' => 'nullable|string',
            'gstin' => 'nullable|string',
        ]);

        $customer->update($request->only([
            'name',
            'mobile',
            'address',
            'gstin'
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
}
