<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\SalesReturn;

class CustomerAccountService
{
    public function getLedger(Customer $customer): array
    {
        $invoices = Invoice::where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->with('returns')
            ->latest('sale_date')
            ->get();

        $payments = CustomerPayment::where('customer_id', $customer->id)
            ->latest('payment_date')
            ->get();

        $returns = SalesReturn::where('customer_id', $customer->id)
            ->latest('return_date')
            ->get();

        $ledger = collect();

        foreach ($invoices as $invoice) {

            $ledger->push([
                'date' => $invoice->sale_date,
                'type' => 'invoice',
                'reference' => $invoice->invoice_no,
                'debit' => $invoice->total + $invoice->returns->sum('total_amount'),
                'credit' => 0,
                'model' => $invoice,
            ]);
        }

        foreach ($payments as $payment) {

            $ledger->push([
                'date' => $payment->payment_date,
                'type' => 'payment',
                'reference' => $payment->reference_no ?? 'Payment',
                'debit' => 0,
                'credit' => $payment->amount,
                'model' => $payment,
            ]);
        }

        foreach ($returns as $salesReturn) {

            $ledger->push([
                'date' => $salesReturn->return_date,
                'type' => 'return',
                'reference' => $salesReturn->return_no,
                'debit' => 0,
                'credit' => $salesReturn->total_amount,
                'model' => $salesReturn,
            ]);
        }

        $ledger = $ledger->sortBy('date')->values();

        $running = 0;

        $ledger = $ledger->map(function ($entry) use (&$running) {

            $running += $entry['debit'];
            $running -= $entry['credit'];

            $entry['running_balance'] = $running;

            return $entry;
        });

        return [
            'ledger' => $ledger,

            'totalSales' => $invoices->sum('total'),

            'totalReceived' => $payments->sum('amount'),

            'totalReturns' => $returns->sum('total_amount'),

            'receivable' => $invoices->sum('remaining_amount'),

            'advance' => $payments->sum('amount') >
                $invoices->sum('total')
                    ? $payments->sum('amount') - $invoices->sum('total')
                    : 0,
        ];
    }
}
