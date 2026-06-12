@extends('layouts.app')

@section('content')

@php
    $remainingSeats = $userLimit === null ? null : max($userLimit - $activeUserCount, 0);
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Team Users</h3>
        <div class="text-muted">
            Manage staff access for {{ $tenant->name }} on the {{ $tenant->activeSubscription?->plan?->name ?? 'current' }} plan.
        </div>
    </div>

    <a href="{{ route('team.create') }}"
       class="btn btn-primary {{ $remainingSeats === 0 ? 'disabled' : '' }}"
       @if($remainingSeats === 0) aria-disabled="true" @endif>
        Add User
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Users</div>
                <div class="h2 mb-0">{{ number_format($totalUserCount) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Active Users</div>
                <div class="h2 mb-0">{{ number_format($activeUserCount) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Inactive Users</div>
                <div class="h2 mb-0 text-secondary">{{ number_format($inactiveUserCount) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Available Seats</div>
                <div class="h2 mb-0 {{ $remainingSeats === 0 ? 'text-danger' : 'text-success' }}">
                    {{ $remainingSeats ?? 'Unlimited' }}
                </div>
                <div class="text-muted small">Limit: {{ $userLimit ?? 'Unlimited' }}</div>
            </div>
        </div>
    </div>
</div>

@if($remainingSeats === 0)
    <div class="alert alert-warning">
        Your current plan has reached its active user limit. Deactivate a staff user or upgrade the plan before adding another user.
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th class="text-end" style="min-width: 190px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $member->name }}</div>
                            <div class="text-muted small">{{ $member->email }}</div>
                        </td>
                        <td>
                            @php
                                $role = $member->roles->first()?->name;
                            @endphp
                            <div class="fw-semibold">
                                {{ str($role ?? 'No role')->replace('_', ' ')->title() }}
                            </div>
                            @if($role && isset($roleDescriptions[$role]))
                                <div class="text-muted small">{{ $roleDescriptions[$role] }}</div>
                            @endif
                        </td>
                        <td>
                            @if($member->requires_password_setup && in_array($member->email, $validInvitationEmails, true))
                                <span class="badge bg-warning text-dark">Invitation Pending</span>
                            @elseif($member->requires_password_setup)
                                <span class="badge bg-danger text-white">Invitation Expired</span>
                            @elseif($member->is_active)
                                <span class="badge bg-success text-white">Active</span>
                            @else
                                <span class="badge bg-light text-dark border">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $member->created_at?->format('M d, Y') }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 flex-nowrap">
                                <a href="{{ route('team.edit', $member) }}"
                                   class="btn btn-sm btn-outline-secondary text-nowrap">
                                    Edit
                                </a>

                                @if($member->id !== auth()->id())
                                    <form method="POST"
                                          action="{{ route('team.resend-invitation', $member) }}"
                                          class="m-0">
                                        @csrf

                                        <button class="btn btn-sm btn-outline-primary text-nowrap">
                                            Resend Invitation
                                        </button>
                                    </form>

                                    @if($member->is_active)
                                        <form method="POST"
                                              action="{{ route('team.deactivate', $member) }}"
                                              class="m-0">
                                            @csrf
                                            @method('PATCH')

                                            <button class="btn btn-sm btn-outline-danger text-nowrap">
                                                Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('team.activate', $member) }}"
                                              class="m-0">
                                            @csrf
                                            @method('PATCH')

                                            <button class="btn btn-sm btn-outline-success text-nowrap">
                                                Activate
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No team users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $members->links() }}
</div>

<div class="mt-4">
    <h4 class="mb-2">Role Access Guide</h4>
    <div class="table-responsive border rounded">
        <table class="table table-sm table-vcenter mb-0">
            <tbody>
                @foreach($roleDescriptions as $role => $description)
                    <tr>
                        <th class="ps-3" style="width: 180px;">
                            {{ str($role)->replace('_', ' ')->title() }}
                        </th>
                        <td class="text-muted">{{ $description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
