<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Piece', 'symbol' => 'pc', 'description' => 'For items sold individually, such as tools or bottles.'],
            ['name' => 'Box', 'symbol' => 'box', 'description' => 'For supplier boxes containing smaller stock units.'],
            ['name' => 'Carton', 'symbol' => 'ctn', 'description' => 'For large supplier cartons containing boxes or pieces.'],
            ['name' => 'Pack', 'symbol' => 'pack', 'description' => 'For grouped items sold or purchased as one pack.'],
            ['name' => 'Tablet', 'symbol' => 'tab', 'description' => 'For medicine stock maintained as individual tablets.'],
            ['name' => 'Strip', 'symbol' => 'strip', 'description' => 'For medicine strips containing multiple tablets.'],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'description' => 'For products measured and sold by weight in kilograms.'],
            ['name' => 'Gram', 'symbol' => 'g', 'description' => 'For products measured in small weight quantities.'],
            ['name' => 'Litre', 'symbol' => 'L', 'description' => 'For liquids measured and sold in litres.'],
            ['name' => 'Millilitre', 'symbol' => 'ml', 'description' => 'For liquids measured in small quantities.'],
        ];

        foreach (Tenant::all() as $tenant) {
            foreach ($units as $unit) {
                Unit::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $unit['name']],
                    [...$unit, 'is_active' => true]
                );
            }
        }
    }
}
