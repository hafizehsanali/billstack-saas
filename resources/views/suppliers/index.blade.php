@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">

    <h3 class="mb-0">Suppliers</h3>

    <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
        + Add Supplier
    </a>

</div>

<div class="card">

    <div class="table-responsive">

        <table class="table table-bordered table-striped mb-0">

            <thead class="table-dark">

                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th width="180">Actions</th>
                </tr>

            </thead>

            <tbody>

                @forelse($suppliers as $supplier)

                    <tr>

                        <td>{{ $loop->iteration }}</td>

                        <td>{{ $supplier->name }}</td>

                        <td>{{ $supplier->phone ?? '-' }}</td>

                        <td>{{ $supplier->email ?? '-' }}</td>

                        <td>{{ $supplier->address ?? '-' }}</td>

                        <td class="d-flex gap-2">

                            <a href="{{ route('suppliers.edit', $supplier->id) }}"
                               class="btn btn-sm btn-warning">
                                Edit
                            </a>

                            <form action="{{ route('suppliers.destroy', $supplier->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Delete this supplier?')">

                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-danger">
                                    Delete
                                </button>

                            </form>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="text-center text-muted">
                            No suppliers found
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection