@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Team User</h3>

        <a href="{{ route('team.index') }}"
           class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('team.update', $member) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $member->name) }}"
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
                           value="{{ old('email', $member->email) }}"
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
                            @disabled($member->id === auth()->id())
                            required>
                        @if($member->id === auth()->id() && $currentRole === 'owner')
                            <option value="owner" selected>Owner</option>
                        @endif
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $currentRole) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @if($member->id === auth()->id())
                        <input type="hidden" name="role" value="{{ $currentRole }}">
                    @endif
                    @error('role')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror">
                    @error('password')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password"
                           name="password_confirmation"
                           class="form-control">
                </div>
            </div>

            <button class="btn btn-primary">
                Update User
            </button>
        </form>
    </div>
</div>

@endsection
