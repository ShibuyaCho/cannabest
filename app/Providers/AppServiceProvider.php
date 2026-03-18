<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\View; // Use the facade
use illuminate\Support\Facades\Auth;
use App\Inventory;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        // ...
        Brand::class => BrandPolicy::class,
    ];

 public function register()
{
    // …

    $this->app->bind('path.public', function() {
        return base_path('public');
        // or return '/home/sys1/Documents/pos2/public';
    });
}

  public function boot()
{
    View::composer('*', function($view) {
        $orgId = Auth::check() ? Auth::user()->organization_id : null;

        // If no org (guest), don't query to avoid leaking other orgs' rows
        $lowInventoryItems = collect();

        if ($orgId) {
            $lowInventoryItems = Inventory::query()
                ->where('organization_id', $orgId) // ✅ make it org-specific
                ->whereColumn('storeQty', '<=', 'min_qty')
                ->get();
        }

        $view->with('lowInventoryItems', $lowInventoryItems);
    });

    if (config('app.env') === 'production') {
        \URL::forceScheme('https');
    }
}
}





