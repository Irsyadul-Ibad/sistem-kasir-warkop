<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kopi = Category::create(['name' => 'Kopi', 'type' => 'menu', 'sort_order' => 1]);
        $makan = Category::create(['name' => 'Makanan', 'type' => 'menu', 'sort_order' => 2]);
        
        Menu::create(['category_id' => $kopi->id, 'name' => 'Kopi Hitam', 'price' => 5000]);
        Menu::create(['category_id' => $kopi->id, 'name' => 'Kopi Susu', 'price' => 8000]);
        Menu::create(['category_id' => $makan->id, 'name' => 'Indomie Telur', 'price' => 12000]);
    }
}
