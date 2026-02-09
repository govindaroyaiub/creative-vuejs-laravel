<?php

namespace App\Observers;

use App\Models\newVersion;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class VersionObserver
{
    /**
     * Handle the newVersion "created" event.
     */
    public function created(newVersion $version): void
    {
        NotificationService::notifyVersionCreated($version, Auth::id());
    }
}
