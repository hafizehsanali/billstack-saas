@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create Feature</h3>
        <a href="{{ route('platform.features.index') }}" class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('platform.features.store') }}">
            @csrf

            @include('platform.features.partials.form', ['feature' => null])

            <button class="btn btn-primary">
                Save Feature
            </button>
        </form>
    </div>
</div>
@endsection
