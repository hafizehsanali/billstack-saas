@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create Team User</h3>

        <a href="{{ route('team.index') }}"
           class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('team.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror"
                           required>
                    @error('name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           required>
                    @error('email')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role"
                            class="form-select @error('role') is-invalid @enderror"
                            required>
                        <option value="">Select role</option>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-hint">
                        @foreach($roleDescriptions as $value => $description)
                            <div><strong>{{ $roles[$value] }}:</strong> {{ $description }}</div>
                        @endforeach
                    </div>
                    @error('role')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-12 mb-3">
                    <div class="alert alert-info mb-0">
                        The staff member will receive a secure email link to create their password.
                    </div>
                </div>
            </div>

            <button class="btn btn-primary">
                Create User and Send Invitation
            </button>
        </form>
    </div>
</div>

@endsection
