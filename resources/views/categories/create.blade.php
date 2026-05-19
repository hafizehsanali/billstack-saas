@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">
        <h3 class="card-title">Create Category</h3>
    </div>

    <div class="card-body">

        <form method="POST"
              action="{{ route('categories.store') }}">

            @csrf

            <div class="mb-3">
                <label class="form-label">Name</label>

                <input type="text"
                       name="name"
                       class="form-control">
            </div>

            <button class="btn btn-primary">
                Save
            </button>

        </form>

    </div>

</div>

@endsection