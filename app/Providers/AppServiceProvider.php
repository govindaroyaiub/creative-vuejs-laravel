<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use App\Models\newVersion;
use App\Observers\CategoryObserver;
use App\Observers\FeedbackObserver;
use App\Observers\FeedbackSetObserver;
use App\Observers\VersionObserver;

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
        // Register model observers for notifications
        newCategory::observe(CategoryObserver::class);
        newFeedback::observe(FeedbackObserver::class);
        newFeedbackSet::observe(FeedbackSetObserver::class);
        newVersion::observe(VersionObserver::class);

        // Configure Pulse authorization using permission system
        Gate::define('viewPulse', function ($user) {
            return $user->canAccess('pulse');
        });
    }
}
