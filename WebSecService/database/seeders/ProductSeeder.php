<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'code' => 'LPT001',
                'name' => 'Laptop Pro X',
                'price' => 999.99,
                'model' => 'LP2023X',
                'description' => 'Professional laptop with high performance specs',
                'photo' => 'lgtv50.jpg',
                'stock_quantity' => 10
            ],
            [
                'code' => 'PHONE001',
                'name' => 'SmartPhone Plus',
                'price' => 699.99,
                'model' => 'SP2023',
                'description' => 'Latest smartphone with advanced camera features',
                'photo' => 'tv2.jpg',
                'stock_quantity' => 15
            ],
            [
                'code' => 'TAB001',
                'name' => 'Tablet Air',
                'price' => 399.99,
                'model' => 'TA2023',
                'description' => 'Lightweight tablet with long battery life',
                'photo' => 'tv3.jpg',
                'stock_quantity' => 8
            ],
            [
                'code' => 'HEAD001',
                'name' => 'Wireless Headphones',
                'price' => 149.99,
                'model' => 'WH2023',
                'description' => 'Noise-cancelling wireless headphones',
                'photo' => 'rf4.jpg',
                'stock_quantity' => 20
            ],
            [
                'code' => 'WATCH001',
                'name' => 'Smart Watch Pro',
                'price' => 249.99,
                'model' => 'SW2023',
                'description' => 'Smart watch with health monitoring features',
                'photo' => 'rf5.jpg',
                'stock_quantity' => 12
            ]
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['code' => $product['code']],
                $product
            );
        }
    }
}