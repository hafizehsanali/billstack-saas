@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Feature</h3>
        <a href="{{ route('platform.features.index') }}" class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('platform.features.update', $feature) }}">
            @csrf
            @method('PUT')

            @include('platform.features.partials.form', ['feature' => $feature])

            <button class="btn btn-primary">
                Update Feature
            </button>
        </form>
    </div>
</div>
@endsection
