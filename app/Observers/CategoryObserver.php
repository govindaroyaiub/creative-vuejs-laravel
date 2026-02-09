<?php

namespace App\Observers;

use App\Models\newCategory;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class CategoryObserver
{
    /**
     * Handle the newCategory "created" event.
     */
    public function created(newCategory $category): void
    {
        NotificationService::notifyCategoryCreated($category, Auth::id());
    }
}
