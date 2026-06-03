@extends('layouts.app')

@section('content')

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Total Expenses</small>
                <h2 class="mb-0">Rs {{ number_format($totalExpenses, 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">This Month</small>
                <h2 class="mb-0">Rs {{ number_format($monthlyExpenses, 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Categories</small>
                <h2 class="mb-0">{{ $categoryCount }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            Expenses
        </h3>

        <a href="{{ route('expenses.create') }}"
           class="btn btn-primary ms-auto">
            Add Expense
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th class="text-end">Amount</th>
                    <th>Date</th>
                    <th>Notes</th>
                    <th class="text-end" style="min-width: 150px;">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->title }}</td>
                        <td>{{ $expense->category }}</td>
                        <td class="text-end">Rs {{ number_format($expense->amount, 2) }}</td>
                        <td>{{ $expense->expense_date->format('d M Y') }}</td>
                        <td>{{ $expense->notes ?? '-' }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 flex-nowrap">
                                <a href="{{ route('expenses.edit', $expense) }}"
                                   class="btn btn-sm btn-outline-secondary text-nowrap">
                                    Edit
                                </a>

                                <form action="{{ route('expenses.destroy', $expense) }}"
                                      method="POST"
                                      class="m-0"
                                      onsubmit="return confirm('Delete this expense?')">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-outline-danger text-nowrap">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No expenses found. Add expenses to keep profit reports accurate.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $expenses->links() }}
</div>

@endsection
