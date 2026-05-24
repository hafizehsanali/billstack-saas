<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Expense;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [

            'Shop Rent',

            'Electricity',

            'Internet',

            'Salary',
        ];

        foreach (Tenant::all() as $tenant) {

            foreach ($categories as $index => $category) {

                Expense::create([

                    'tenant_id' => $tenant->id,

                    'title' => $category . ' Expense',

                    'category' => $category,

                    'amount' => rand(1000, 10000),

                    'expense_date' => now(),

                    'notes' => 'Demo expense data',
                ]);
            }
        }
    }
}