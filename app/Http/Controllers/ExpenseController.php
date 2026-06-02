<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function index()
    {
        $query = Expense::where('tenant_id', auth()->user()->tenant_id);
        $totalExpenses = (clone $query)->sum('amount');
        $monthlyExpenses = (clone $query)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');
        $categoryCount = (clone $query)->distinct('category')->count('category');

        $expenses = $query
            ->latest('expense_date')
            ->latest()
            ->paginate(10);

        return view(
            'expenses.index',
            compact('expenses', 'totalExpenses', 'monthlyExpenses', 'categoryCount')
        );
    }

    public function create()
    {
        return view('expenses.create');
    }

    public function store(StoreExpenseRequest $request)
    {
        $data = $request->validated();

        Expense::create([
            'tenant_id' => auth()->user()->tenant_id,
            'title' => $data['title'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('expenses.index')
            ->with(
                'success',
                'Expense created successfully.'
            );
    }

    public function edit(Expense $expense)
    {
        abort_if(
            $expense->tenant_id !== auth()->user()->tenant_id,
            403
        );

        return view(
            'expenses.edit',
            compact('expense')
        );
    }

    public function update(
        StoreExpenseRequest $request,
        Expense $expense
    ) {
        abort_if(
            $expense->tenant_id !== auth()->user()->tenant_id,
            403
        );

        $expense->update($request->validated());

        return redirect()
            ->route('expenses.index')
            ->with(
                'success',
                'Expense updated successfully.'
            );
    }

    public function destroy(Expense $expense)
    {
        abort_if(
            $expense->tenant_id !== auth()->user()->tenant_id,
            403
        );

        $expense->delete();

        return back()->with(
            'success',
            'Expense deleted successfully.'
        );
    }
}
