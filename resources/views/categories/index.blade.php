@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Categories</h3>

        <a href="{{ route('categories.create') }}"
           class="btn btn-primary ms-auto">
            Add Category
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">

            <thead>
            <tr>
                <th>Name</th>
            </tr>
            </thead>

            <tbody>

            @foreach($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                </tr>
            @endforeach

            </tbody>

        </table>
    </div>
</div>

@endsection