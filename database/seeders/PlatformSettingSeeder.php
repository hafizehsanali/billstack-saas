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
                'allow_registration' => true,
            ]
        );
    }
}
