@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Platform Activity</h1>
        <div class="text-muted">Review important administrative changes across the platform.</div>
    </div>

    <form method="GET" class="d-flex gap-2">
        <select name="action" class="form-select">
            <option value="">All actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected($selectedAction === $action)>
                    {{ str($action)->replace('.', ' ')->title() }}
                </option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary">Filter</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Business</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td>
                            <div>{{ $activity->created_at?->format('M d, Y') }}</div>
                            <div class="text-muted small">{{ $activity->created_at?->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div>{{ $activity->actor?->name ?? 'System' }}</div>
                            <div class="text-muted small">{{ $activity->actor?->email }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ str($activity->action)->replace('.', ' ')->title() }}
                            </span>
                        </td>
                        <td>{{ $activity->tenant?->name ?? '-' }}</td>
                        <td>
                            <div>{{ $activity->description }}</div>
                            @if($activity->subject_label)
                                <div class="text-muted small">{{ $activity->subject_label }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No platform activity recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $activities->links() }}</div>
@endsection
