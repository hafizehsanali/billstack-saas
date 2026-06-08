@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create Plan</h3>
        <a href="{{ route('platform.plans.index') }}" class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('platform.plans.store') }}">
            @csrf

            @include('platform.plans.partials.form', [
                'plan' => null,
                'features' => $features,
                'selectedFeatures' => $selectedFeatures,
            ])

            <button class="btn btn-primary">
                Save Plan
            </button>
        </form>
    </div>
</div>
@endsection
