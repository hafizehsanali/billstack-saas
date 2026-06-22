@extends('layouts.app')

@section('content')
    @include('products.partials.form', [
        'title' => 'Edit Product',
        'action' => route('products.update', $product),
        'method' => 'PUT',
    ])
@endsection
