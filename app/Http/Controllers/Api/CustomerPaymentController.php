<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerLedger;
use Illuminate\Support\Facades\DB;

use App\Helpers\ResponseHelper;

class CustomerPaymentController extends Controller
{
    public function store(Request $request, $customer_uuid)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string'
        ]);

        return DB::transaction(function () use ($request, $customer_uuid) {

            $customer = Customer::where('customer_uuid', $customer_uuid)
                ->where('tenant_uuid', app('tenant_uuid'))
                ->firstOrFail();

            $customer->decrement('credit_balance', $request->amount);

            CustomerLedger::create([
                'tenant_uuid' => app('tenant_uuid'),
                'customer_uuid' => $customer_uuid,
                'type' => 'payment',
                'amount' => $request->amount,
                'reference_uuid' => null,
                'note' => 'Customer payment',
            ]);

            return ResponseHelper::success([
                'balance' => $customer->credit_balance
            ], 'Payment recorded');
        });
    }
}
