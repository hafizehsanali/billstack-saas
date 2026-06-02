@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            Edit Expense
        </h3>

        <a href="{{ route('expenses.index') }}"
           class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST"
              action="{{ route('expenses.update', $expense) }}">
            @include('expenses._form', ['expense' => $expense])
        </form>
    </div>
</div>

@endsection
