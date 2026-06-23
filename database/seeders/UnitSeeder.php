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
            ['name' => 'Bag', 'symbol' => 'bag', 'description' => 'For supplier bags containing loose stock, such as rice, sugar, or flour.'],
            ['name' => 'Bottle', 'symbol' => 'btl', 'description' => 'For bottled items that are bought or sold as one bottle.'],
            ['name' => 'Tray', 'symbol' => 'tray', 'description' => 'For supplier trays containing pieces, such as eggs.'],
            ['name' => 'Dozen', 'symbol' => 'doz', 'description' => 'For products grouped in twelve pieces.'],
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
