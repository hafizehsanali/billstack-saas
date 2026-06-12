<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'platform_name',
        'support_email',
        'support_phone',
        'currency_code',
        'payment_instructions',
        'payment_channels',
        'allow_registration',
    ];

    protected function casts(): array
    {
        return [
            'allow_registration' => 'boolean',
            'payment_channels' => 'array',
        ];
    }

    public function activePaymentChannels(): array
    {
        return collect($this->payment_channels ?? self::defaultPaymentChannels())
            ->filter(fn (array $channel) => (bool) ($channel['is_active'] ?? false))
            ->values()
            ->all();
    }

    public static function defaultPaymentChannels(): array
    {
        return [
            [
                'key' => 'bank_transfer',
                'label' => 'Bank Transfer',
                'account_title' => null,
                'account_number' => null,
                'instructions' => 'Contact support for the account details before making payment.',
                'is_active' => true,
            ],
        ];
    }

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'platform_name' => 'BillStack',
            'currency_code' => 'PKR',
            'payment_channels' => self::defaultPaymentChannels(),
            'allow_registration' => true,
        ]);
    }
}
