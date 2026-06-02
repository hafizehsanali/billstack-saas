@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Profit & Loss Report</h2>
        <small class="text-muted">
            {{ $startDate->format('d M Y') }} to {{ $endDate->format('d M Y') }}
        </small>
    </div>

    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
        Dashboard
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Start Date</label>
                <input type="date"
                       name="start_date"
                       value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
                       class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date"
                       name="end_date"
                       value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                       class="form-control">
            </div>

            <div class="col-md-4">
                <button class="btn btn-primary">
                    Filter
                </button>

                <a href="{{ route('reports.profit-loss') }}"
                   class="btn btn-secondary">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Sales</small>
                <h2 class="mb-0">Rs {{ number_format($sales, 2) }}</h2>
                <small class="text-muted">{{ $invoiceCount }} invoice{{ $invoiceCount === 1 ? '' : 's' }}</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">COGS</small>
                <h2 class="mb-0 text-danger">Rs {{ number_format($cogs, 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Gross Profit</small>
                <h2 class="mb-0 {{ $grossProfit >= 0 ? 'text-success' : 'text-danger' }}">
                    Rs {{ number_format($grossProfit, 2) }}
                </h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Net Profit</small>
                <h2 class="mb-0 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                    Rs {{ number_format($netProfit, 2) }}
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            Calculation Summary
        </h3>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <tbody>
                <tr>
                    <th>Sales</th>
                    <td class="text-end">Rs {{ number_format($sales, 2) }}</td>
                </tr>

                <tr>
                    <th>Less: Cost of Goods Sold</th>
                    <td class="text-end text-danger">Rs {{ number_format($cogs, 2) }}</td>
                </tr>

                <tr>
                    <th>Gross Profit</th>
                    <td class="text-end fw-bold {{ $grossProfit >= 0 ? 'text-success' : 'text-danger' }}">
                        Rs {{ number_format($grossProfit, 2) }}
                    </td>
                </tr>

                <tr>
                    <th>Less: Expenses</th>
                    <td class="text-end text-danger">Rs {{ number_format($expenses, 2) }}</td>
                </tr>

                <tr>
                    <th>Net Profit</th>
                    <td class="text-end fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                        Rs {{ number_format($netProfit, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
