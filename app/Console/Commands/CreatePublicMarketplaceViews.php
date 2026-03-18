<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreatePublicMarketplaceViews extends Command
{
    protected $signature = 'make:public-marketplace-views';

    protected $description = 'Create view files for public retail and wholesale marketplaces';

    public function handle()
    {
        $views = [
            'retail' => 'resources/views/retail/public-marketplace.blade.php',
            'wholesale' => 'resources/views/wholesale/public-marketplace.blade.php',
        ];

        foreach ($views as $type => $path) {
            if (File::exists($path)) {
                $this->error("The {$type} public marketplace view already exists!");
            } else {
                $content = $this->getViewContent($type);
                File::put($path, $content);
                $this->info("The {$type} public marketplace view has been created successfully!");
            }
        }
    }

    private function getViewContent($type)
    {
        return <<<BLADE
@extends('layouts.customer')

@section('content')
<div class="container">
    <h1>{$type} Marketplace</h1>
    <p>Browse our {$type} partners and their products:</p>

    @foreach(\$organizations as \$organization)
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">{{ \$organization->name }}</h5>
                <p class="card-text">Products available: {{ \$organization->products_count }}</p>
                <a href="{{ route('login') }}" class="btn btn-primary">Log in to view products</a>
            </div>
        </div>
    @endforeach

    <div class="mt-4">
        <p>Don't have an account? <a href="{{ route('register') }}">Sign up now</a> to start shopping!</p>
    </div>
</div>
@endsection
BLADE;
    }
}