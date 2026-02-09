<?php

namespace App\Observers;

use App\Models\newFeedback;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class FeedbackObserver
{
    /**
     * Handle the newFeedback "created" event.
     */
    public function created(newFeedback $feedback): void
    {
        NotificationService::notifyFeedbackCreated($feedback, Auth::id());
    }
}
