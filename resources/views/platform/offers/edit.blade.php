@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Offer</h3>
        <a href="{{ route('platform.offers.index') }}" class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('platform.offers.update', $offer) }}">
            @csrf
            @method('PUT')

            @include('platform.offers.partials.form', [
                'offer' => $offer,
                'plans' => $plans,
                'selectedPlans' => $selectedPlans,
            ])

            <button class="btn btn-primary">
                Update Offer
            </button>
        </form>
    </div>
</div>
@endsection
