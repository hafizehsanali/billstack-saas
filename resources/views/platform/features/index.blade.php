@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Features</h1>
        <div class="text-muted">Define free and paid capabilities used by subscription plans.</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
            Platform
        </a>
        <a href="{{ route('platform.features.create') }}" class="btn btn-primary">
            Add Feature
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>Key</th>
                    <th>Type</th>
                    <th>Plans</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($features as $feature)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $feature->name }}</div>
                            <div class="text-muted small">{{ $feature->description ?? 'No description' }}</div>
                        </td>
                        <td><code>{{ $feature->key }}</code></td>
                        <td>
                            <span class="badge {{ $feature->is_paid ? 'bg-primary text-white' : 'bg-light text-dark border' }}">
                                {{ $feature->is_paid ? 'Paid' : 'Free' }}
                            </span>
                        </td>
                        <td>{{ $feature->plans_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('platform.features.edit', $feature) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No features found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $features->links() }}
</div>
@endsection
