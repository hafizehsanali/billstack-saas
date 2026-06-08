@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Plan</h3>
        <a href="{{ route('platform.plans.index') }}" class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('platform.plans.update', $plan) }}">
            @csrf
            @method('PUT')

            @include('platform.plans.partials.form', [
                'plan' => $plan,
                'features' => $features,
                'selectedFeatures' => $selectedFeatures,
            ])

            <button class="btn btn-primary">
                Update Plan
            </button>
        </form>
    </div>
</div>
@endsection
