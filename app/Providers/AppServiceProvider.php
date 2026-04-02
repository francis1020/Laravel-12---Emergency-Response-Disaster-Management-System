<?php

namespace App\Providers;

use App\Models\EmergencyType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share emergency types and validation flags with all views that use the app layout
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            
            $view->with([
                'emergencyTypes' => EmergencyType::where('status', true)->orderBy('name')->get(),
                'isValidatedUser' => $user && $user->email_verified_at !== null,
                'isValidatedResponder' => $user && $user->isResponder() && $user->email_verified_at !== null,
            ]);
        });
    }
}

