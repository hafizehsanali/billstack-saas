<?php

namespace App\Http\Controllers;

use App\Models\CustomerPayment;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $request->validate([

            'amount' => [
                'required',
                'numeric',
                'min:1',
            ],

            'payment_method' => [
                'required',
                'string',
                'max:50',
            ],

            'payment_date' => [
                'nullable',
                'date',
            ],

            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

        ]);
        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'Cannot record payment against a cancelled invoice.');
        }
        $paidAmount = $invoice->payments()->sum('amount');
        $remaining = $invoice->total - $paidAmount;
        if ($request->amount > $remaining) {

            return back()->withErrors([
                'amount' => 'Payment exceeds remaining balance.',
            ]);
        }

        CustomerPayment::create([

            'tenant_id' => auth()->user()->tenant_id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date
                ? Carbon::parse($request->payment_date)->toDateString()
                : now()->toDateString(),
            'reference_no' => $request->reference_no,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
        ]);

        $newPaidAmount = $invoice->payments()->sum('amount');

        if ($newPaidAmount >= $invoice->total) {

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => 0,
                'status' => 'paid',
            ]);

        } else {

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => $invoice->total - $newPaidAmount,
                'status' => 'partial',
            ]);
        }

        return back()->with(
            'success',
            'Payment recorded successfully.'
        );
    }
}
