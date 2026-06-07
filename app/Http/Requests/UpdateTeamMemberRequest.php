<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('owner') === true;
    }

    public function rules(): array
    {
        $memberId = $this->route('teamMember')?->id;

        $roles = $memberId === $this->user()?->id
            ? ['owner', 'manager', 'accountant', 'cashier', 'inventory_staff']
            : ['manager', 'accountant', 'cashier', 'inventory_staff'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($memberId)],
            'role' => ['required', Rule::in($roles)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
