@extends('layouts.app')
@section('content')
<div class="container mt-4">

    <form
        method="POST"
        action="{{ isset($expense)
            ? route('expenses.update', $expense->id)
            : route('expenses.store')
        }}"
    >

        @csrf

        @if(isset($expense))
            @method('PUT')
        @endif

        <div class="mb-3">

            <label>Title</label>

            <input
                type="text"
                name="title"
                class="form-control"
                value="{{ $expense->title ?? '' }}"
            >

        </div>

        <div class="mb-3">

            <label>Category</label>

            <input
                type="text"
                name="category"
                class="form-control"
                value="{{ $expense->category ?? '' }}"
            >

        </div>

        <div class="mb-3">

            <label>Amount</label>

            <input
                type="number"
                step="0.01"
                name="amount"
                class="form-control"
                value="{{ $expense->amount ?? '' }}"
            >

        </div>

        <div class="mb-3">

            <label>Date</label>

            <input
                type="date"
                name="expense_date"
                class="form-control"
                value="{{ $expense->expense_date ?? '' }}"
            >

        </div>

        <div class="mb-3">

            <label>Notes</label>

            <textarea
                name="notes"
                class="form-control"
            >{{ $expense->notes ?? '' }}</textarea>

        </div>

        <button class="btn btn-success">

            Save Expense

        </button>

    </form>

</div>
@endsection