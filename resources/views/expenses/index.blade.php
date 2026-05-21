@extends('layouts.app')

@section('content')

  <div class="container mt-4">

    <div class="d-flex justify-content-between mb-3">

        <h2>Expenses</h2>

        <a
            href="{{ route('expenses.create') }}"
            class="btn btn-primary"
        >
            Add Expense
        </a>

    </div>

    <table class="table table-bordered">

        <thead>

            <tr>

                <th>Title</th>

                <th>Category</th>

                <th>Amount</th>

                <th>Date</th>

                <th>Actions</th>

            </tr>

        </thead>

        <tbody>

            @foreach($expenses as $expense)

                <tr>

                    <td>
                        {{ $expense->title }}
                    </td>

                    <td>
                        {{ $expense->category }}
                    </td>

                    <td>
                        {{ $expense->amount }}
                    </td>

                    <td>
                        {{ $expense->expense_date }}
                    </td>

                    <td>

                        <a
                            href="{{ route('expenses.edit', $expense->id) }}"
                            class="btn btn-warning btn-sm"
                        >
                            Edit
                        </a>

                        <form
                            action="{{ route('expenses.destroy', $expense->id) }}"
                            method="POST"
                            class="d-inline"
                        >

                            @csrf
                            @method('DELETE')

                            <button
                                class="btn btn-danger btn-sm"
                            >
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

</div>
@endsection