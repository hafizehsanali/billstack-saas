@extends('layouts.app')
@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            Add Expense
        </h3>

        <a href="{{ route('expenses.index') }}"
           class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST"
              action="{{ route('expenses.store') }}">
            @include('expenses._form')
        </form>
    </div>
</div>

@endsection
