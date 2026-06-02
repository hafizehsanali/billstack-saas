<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerPaymentAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    public function store(
        Request $request,
        Customer $customer,
        CustomerPaymentAllocationService $paymentAllocation
    ): RedirectResponse {
        abort_if(
            $customer->tenant_id !== auth()->user()->tenant_id,
            403
        );

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_date' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $paymentAllocation->record($customer, $validated);

        return back()->with(
            'success',
            'Customer payment recorded successfully.'
        );
    }
}
