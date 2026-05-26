@extends('layouts.app')

@section('content')
<div class="container">

<h3>{{ $supplier->name }} Ledger</h3>

{{-- Summary --}}
<div class="row mb-3">
<div class="col">Total Purchases: <b>{{ number_format($total_purchases,2) }}</b></div>
<div class="col">Total Payments: <b>{{ number_format($total_payments,2) }}</b></div>
<div class="col">Payable Amount: <b>{{ number_format($remaining_amount,2) }}</b></div>
</div>

{{-- Ledger Table --}}
<table class="table table-bordered">
<thead>
<tr>
<th>Date</th>
<th>Type</th>
<th>Ref</th>
<th>Debit</th>
<th>Credit</th>
<th>Payable Amount</th>
<th>Notes</th>
</tr>
</thead>

<tbody>
@php $payableAmount = 0; @endphp

@foreach($ledger as $row)
@php
$payableAmount += $row['debit'] - $row['credit'];
@endphp

<tr>
<td>{{ $row['date'] }}</td>
<td>
<span class="badge bg-{{ $row['type']=='purchase'?'danger':'success' }}">
{{ ucfirst($row['type']) }}
</span>
</td>
<td>{{ $row['ref'] }}</td>
<td>{{ number_format($row['debit'],2) }}</td>
<td>{{ number_format($row['credit'],2) }}</td>
<td><b>{{ number_format($payableAmount,2) }}</b></td>
<td>{{ $row['notes'] }}</td>
</tr>
@endforeach

</tbody>
</table>

</div>
@endsection