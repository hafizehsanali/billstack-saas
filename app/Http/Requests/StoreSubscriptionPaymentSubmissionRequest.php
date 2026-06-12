<?php

namespace App\Http\Requests;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPaymentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('owner') === true;
    }

    public function rules(): array
    {
        $channelKeys = collect(PlatformSetting::current()->activePaymentChannels())
            ->pluck('key')
            ->all();

        return [
            'payment_method' => [
                'required',
                Rule::in($channelKeys),
            ],
            'reference_no' => ['required', 'string', 'max:100'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
