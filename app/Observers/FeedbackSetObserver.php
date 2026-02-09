<?php

namespace App\Observers;

use App\Models\newFeedbackSet;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class FeedbackSetObserver
{
    /**
     * Handle the newFeedbackSet "created" event.
     */
    public function created(newFeedbackSet $feedbackSet): void
    {
        NotificationService::notifyFeedbackSetCreated($feedbackSet, Auth::id());
    }
}
