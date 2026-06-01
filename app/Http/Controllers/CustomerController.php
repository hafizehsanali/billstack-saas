<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use Carbon\Carbon;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::where('tenant_id', auth()->user()->tenant_id)
            ->withCount('invoices')
            ->latest()
            ->paginate(10);

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request)
    {

        $data = $request->validated();
        Customer::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
        ]);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function statement(Customer $customer)
    {
        abort_if(
            $customer->tenant_id !== auth()->user()->tenant_id,
            403
        );

        // Date filters
        $startDate = request('start_date')
            ? Carbon::parse(request('start_date'))->startOfDay()
            : null;

        $endDate = request('end_date')
            ? Carbon::parse(request('end_date'))->endOfDay()
            : null;

        // Customer invoices
        $invoices = $customer->invoices()
            ->where('status', '!=', 'cancelled')
            ->with('returns')
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
            ->get()
            ->map(function ($invoice) {
                return [
                    'date' => $invoice->created_at,
                    'type' => 'Invoice',
                    'reference' => $invoice->invoice_no,
                    'debit' => $invoice->total + $invoice->returns->sum('total_amount'),
                    'credit' => 0,
                ];
            });

        // Customer payments
        $payments = $customer->payments()
            ->when($startDate, fn ($q) => $q->where('customer_payments.created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('customer_payments.created_at', '<=', $endDate))
            ->get()
            ->map(function ($payment) {
                return [
                    'date' => $payment->created_at,
                    'type' => 'Payment',
                    'reference' => $payment->reference_no ?? 'PAY-'.$payment->id,
                    'debit' => 0,
                    'credit' => $payment->amount,
                ];
            });

        $returns = $customer->returns()
            ->when($startDate, fn ($q) => $q->where('return_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('return_date', '<=', $endDate))
            ->get()
            ->map(function ($salesReturn) {
                return [
                    'date' => $salesReturn->return_date,
                    'type' => 'Sales Return',
                    'reference' => $salesReturn->return_no,
                    'debit' => 0,
                    'credit' => $salesReturn->total_amount,
                ];
            });

        // Merge + sort ledger entries
        $entries = $invoices
            ->concat($payments)
            ->concat($returns)
            ->sortBy('date')
            ->values();

        // Running Amount
        $remainingAmount = 0;

        $entries = $entries->map(function ($entry) use (&$remainingAmount) {

            $remainingAmount += $entry['debit'];
            $remainingAmount -= $entry['credit'];

            $entry['remaining_amount'] = $remainingAmount;

            return $entry;
        });

        return view('customers.statement', compact(
            'customer',
            'entries'
        ));
    }
}
