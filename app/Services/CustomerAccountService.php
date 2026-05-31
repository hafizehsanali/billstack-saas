<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;

class CustomerAccountService
{
    public function getLedger(Customer $customer): array
    {
        $invoices = Invoice::where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->latest('sale_date')
            ->get();

        $payments = CustomerPayment::where('customer_id', $customer->id)
            ->latest('payment_date')
            ->get();

        $ledger = collect();

        foreach ($invoices as $invoice) {

            $ledger->push([
                'date' => $invoice->sale_date,
                'type' => 'invoice',
                'reference' => $invoice->invoice_no,
                'debit' => $invoice->total,
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

            'receivable' => $invoices->sum('remaining_amount'),

            'advance' => $payments->sum('amount') >
                $invoices->sum('total')
                    ? $payments->sum('amount') - $invoices->sum('total')
                    : 0,
        ];
    }
}
