@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create Offer</h3>
        <a href="{{ route('platform.offers.index') }}" class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('platform.offers.store') }}">
            @csrf

            @include('platform.offers.partials.form', [
                'offer' => null,
                'plans' => $plans,
                'selectedPlans' => $selectedPlans,
            ])

            <button class="btn btn-primary">
                Save Offer
            </button>
        </form>
    </div>
</div>
@endsection
