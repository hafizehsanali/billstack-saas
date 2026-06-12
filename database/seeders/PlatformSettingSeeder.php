<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        PlatformSetting::updateOrCreate(
            ['id' => 1],
            [
                'platform_name' => 'BillStack',
                'support_email' => 'support@billstack.test',
                'support_phone' => '+92 300 0000000',
                'currency_code' => 'PKR',
                'payment_instructions' => 'Transfer the full invoice amount using the approved payment channel, then share the invoice number and transaction reference with support.',
                'payment_channels' => [
                    [
                        'key' => 'bank_transfer',
                        'label' => 'Bank Transfer',
                        'account_title' => 'BillStack',
                        'account_number' => 'Configure the production account',
                        'instructions' => 'Use the subscription invoice number as the transfer reference.',
                        'is_active' => true,
                    ],
                    [
                        'key' => 'jazzcash',
                        'label' => 'JazzCash',
                        'account_title' => 'BillStack',
                        'account_number' => 'Configure the production wallet',
                        'instructions' => 'Enter the wallet transaction ID after completing payment.',
                        'is_active' => false,
                    ],
                    [
                        'key' => 'easypaisa',
                        'label' => 'Easypaisa',
                        'account_title' => 'BillStack',
                        'account_number' => 'Configure the production wallet',
                        'instructions' => 'Enter the wallet transaction ID after completing payment.',
                        'is_active' => false,
                    ],
                ],
                'allow_registration' => true,
            ]
        );
    }
}
