<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DashboardConfig;

class DashboardConfigSeeder extends Seeder
{
    public function run()
    {
        DashboardConfig::create([
            'name' => 'Default',
            'layout' => json_encode([
                [
                    ['width' => 6, 'widgets' => ['sales_summary']],
                    ['width' => 6, 'widgets' => ['top_products']]
                ]
            ]),
            'widgets' => json_encode([
                'sales_summary' => [
                    'id' => 'sales_summary',
                    'type' => 'sales_summary',
                    'title' => 'Sales Summary'
                ],
                'top_products' => [
                    'id' => 'top_products',
                    'type' => 'top_products',
                    'title' => 'Top Products'
                ]
            ])
        ]);
    }
}