<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\CustomerPayment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $request->validate([

            'amount' => [
                'required',
                'numeric',
                'min:1'
            ],

            'method' => [
                'required'
            ],

        ]);
        if ($invoice->status === 'cancelled')
        {
            return back()->with( 'error','Cannot record payment against a cancelled invoice.');
        }
        // Prevent overpayment
        $paidAmount = $invoice->payments()->sum('amount');
        $remaining = $invoice->total - $paidAmount;
        if ($request->amount > $remaining)
        {

            return back()->withErrors([
                'amount' => 'Payment exceeds remaining balance.'
            ]);
        }

        // Save payment
        CustomerPayment::create([

            'tenant_id' => auth()->user()->tenant_id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'amount' => $request->amount,
            'payment_date'=> $request->payment_date,
            'reference_no' => $request->reference_no,
            'payment_method' => $request->method,
            'notes' => $request->notes,
        ]);

        // Calculate updated total paid
        $newPaidAmount = $invoice->payments()->sum('amount');

        // Auto update status
        if ($newPaidAmount >= $invoice->total) {

            $invoice->update([
                'status' => 'paid'
            ]);

        } else {

            $invoice->update([
                'status' => 'partial'
            ]);
        }

        return back()->with(
            'success',
            'Payment recorded successfully.'
        );
    }
}
