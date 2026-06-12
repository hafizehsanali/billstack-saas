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
        'allow_registration',
    ];

    protected function casts(): array
    {
        return [
            'allow_registration' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'platform_name' => 'BillStack',
            'currency_code' => 'PKR',
            'allow_registration' => true,
        ]);
    }
}
