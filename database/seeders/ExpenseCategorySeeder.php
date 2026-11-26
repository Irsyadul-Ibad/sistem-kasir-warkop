<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Bahan Baku', 'Operasional', 'Gaji', 'Sewa', 'Peralatan', 'Lain-lain'] as $index => $name) {
            Category::firstOrCreate(
                ['name' => $name, 'type' => 'expense'],
                ['sort_order' => $index + 1],
            );
        }
    }
}
