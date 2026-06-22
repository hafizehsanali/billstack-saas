@extends('layouts.app')

@section('content')
    @include('products.partials.form', [
        'title' => 'Create Product',
        'action' => route('products.store'),
        'method' => 'POST',
    ])
@endsection
