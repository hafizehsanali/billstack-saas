<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::where(
            'tenant_id',
            auth()->user()->tenant_id
        )->latest()->get();

        return view(
            'expenses.index',
            compact('expenses')
        );
    }

    public function create()
    {
        return view('expenses.create');
    }

    public function store(Request $request)
    {
        $request->validate([

            'title' => ['required'],

            'category' => ['required'],

            'amount' => ['required', 'numeric'],

            'expense_date' => ['required'],
        ]);

        Expense::create([

            'tenant_id' =>
                auth()->user()->tenant_id,

            'title' => $request->title,

            'category' => $request->category,

            'amount' => $request->amount,

            'expense_date' =>
                $request->expense_date,

            'notes' => $request->notes,
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
        return view(
            'expenses.edit',
            compact('expense')
        );
    }

    public function update(
        Request $request,
        Expense $expense
    ) {

        $request->validate([

            'title' => ['required'],

            'category' => ['required'],

            'amount' => ['required', 'numeric'],

            'expense_date' => ['required'],
        ]);

        $expense->update($request->all());

        return redirect()
            ->route('expenses.index')
            ->with(
                'success',
                'Expense updated successfully.'
            );
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return back()->with(
            'success',
            'Expense deleted successfully.'
        );
    }
}