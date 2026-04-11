<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerLedger;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    public function store(Request $request, $customer_uuid)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string'
        ]);

        DB::beginTransaction();

        try {

            $customer = Customer::where('customer_uuid', $customer_uuid)
                ->where('tenant_uuid', app('tenant_uuid'))
                ->firstOrFail();

            // ✅ Reduce credit balance
            $customer->decrement('credit_balance', $request->amount);

            // ✅ Ledger entry
            CustomerLedger::create([
                'tenant_uuid' => app('tenant_uuid'),
                'customer_uuid' => $customer_uuid,
                'type' => 'payment',
                'amount' => $request->amount,
                'reference_uuid' => null,
                'note' => 'Customer payment',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Payment recorded',
                'balance' => $customer->credit_balance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
