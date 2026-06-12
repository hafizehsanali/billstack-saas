@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Tenants</h1>
        <div class="text-muted">Manage business workspaces and their subscription plans.</div>
    </div>

    <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
        Platform
    </a>
</div>

<form method="GET" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-lg-5">
            <label for="tenant-search" class="form-label">Search businesses</label>
            <input id="tenant-search"
                   type="search"
                   name="search"
                   value="{{ request('search') }}"
                   class="form-control"
                   placeholder="Business name, slug, or owner email">
        </div>
        <div class="col-sm-5 col-lg-3">
            <label for="tenant-plan" class="form-label">Plan</label>
            <select id="tenant-plan" name="plan" class="form-select">
                <option value="">All plans</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected((string) request('plan') === (string) $plan->id)>
                        {{ $plan->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-lg-2">
            <label for="tenant-status" class="form-label">Access</label>
            <select id="tenant-status" name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-sm-3 col-lg-2 d-flex gap-2">
            <button class="btn btn-primary flex-fill"><i data-lucide="search"></i> Filter</button>
            <a href="{{ route('platform.tenants.index') }}" class="btn btn-icon btn-outline-secondary" title="Clear filters">
                <i data-lucide="x"></i>
            </a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th class="text-end">Users</th>
                    <th>Usage</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                    @php
                        $subscription = $tenant->currentSubscription;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('platform.tenants.show', $tenant) }}" class="fw-bold">
                                {{ $tenant->name }}
                            </a>
                            <div class="text-muted small">{{ $tenant->email ?? $tenant->slug }}</div>
                        </td>
                        <td>{{ $subscription?->plan?->name ?? 'Not assigned' }}</td>
                        <td>
                            <span class="badge {{ $subscription?->status === 'active' ? 'bg-success text-white' : 'bg-light text-dark border' }}">
                                {{ $subscription?->status ?? 'pending' }}
                            </span>
                        </td>
                        <td class="text-end">{{ $tenant->users_count }}</td>
                        <td>
                            @php
                                $tenantUsage = $usage[$tenant->id];
                            @endphp
                            <div class="small">
                                Products:
                                <span class="{{ $tenantUsage['products']['status'] === 'limit' ? 'text-danger fw-semibold' : '' }}">
                                    {{ $tenantUsage['products']['used'] }}/{{ $tenantUsage['products']['limit'] ?? 'Unlimited' }}
                                </span>
                            </div>
                            <div class="small text-muted">
                                Invoices this month:
                                <span class="{{ $tenantUsage['monthly_invoices']['status'] === 'limit' ? 'text-danger fw-semibold' : '' }}">
                                    {{ $tenantUsage['monthly_invoices']['used'] }}/{{ $tenantUsage['monthly_invoices']['limit'] ?? 'Unlimited' }}
                                </span>
                            </div>
                        </td>
                        <td>{{ $tenant->created_at?->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('platform.tenants.show', $tenant) }}"
                               class="btn btn-sm btn-outline-primary">
                                View
                            </a>
                            <a href="{{ route('platform.tenants.edit', $tenant) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Subscription
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No tenants found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $tenants->links() }}
</div>
@endsection
