<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardConfig extends Model
{
 use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'layout',
        'widgets',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'layout' => 'array',
        'widgets' => 'array',
    ];

    /**
     * Get the default dashboard configuration.
     *
     * @return self
     */
    public static function getDefault()
    {
        return self::firstOrCreate(
            ['name' => 'Default'],
            [
                'layout' => [
                    [
                        ['width' => 6, 'widgets' => ['sales_summary']],
                        ['width' => 6, 'widgets' => ['top_products']]
                    ]
                ],
                'widgets' => [
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
                ]
            ]
        );
    }
}
