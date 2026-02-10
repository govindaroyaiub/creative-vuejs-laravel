<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Batching state for consolidating multiple notifications
     */
    protected static $batchingEnabled = false;
    protected static $batchedItems = [];
    protected static $batchPreviewId = null;
    protected static $batchActorId = null;

    /**
     * Enable notification batching for bulk operations
     */
    public static function beginBatch($previewId, $actorId = null)
    {
        self::$batchingEnabled = true;
        self::$batchedItems = [];
        self::$batchPreviewId = $previewId;
        self::$batchActorId = $actorId;
    }

    /**
     * Disable batching and send consolidated notification
     */
    public static function endBatch()
    {
        if (self::$batchingEnabled && !empty(self::$batchedItems)) {
            self::sendConsolidatedNotification();
        }

        self::$batchingEnabled = false;
        self::$batchedItems = [];
        self::$batchPreviewId = null;
        self::$batchActorId = null;
    }

    /**
     * Add item to batch
     */
    protected static function addToBatch($type, $data)
    {
        self::$batchedItems[] = [
            'type' => $type,
            'data' => $data,
        ];
    }

    /**
     * Send a consolidated notification for preview creation with all components
     */
    protected static function sendConsolidatedNotification()
    {
        $preview = \App\Models\newPreview::find(self::$batchPreviewId);
        if (!$preview) {
            return;
        }

        $categories = [];
        $feedbacks = [];
        $feedbackSets = [];
        $versions = [];

        foreach (self::$batchedItems as $item) {
            switch ($item['type']) {
                case 'category':
                    $categories[] = $item['data']['category_name'];
                    break;
                case 'feedback':
                    $feedbacks[] = $item['data']['feedback_name'];
                    break;
                case 'feedback_set':
                    $feedbackSets[] = $item['data']['feedback_set_name'];
                    break;
                case 'version':
                    $versions[] = $item['data']['version_name'];
                    break;
            }
        }

        // Build a consolidated message
        $message = "New preview '{$preview->name}' was created with ";
        $parts = [];

        if (!empty($categories)) {
            $parts[] = count($categories) . " " . (count($categories) === 1 ? "category" : "categories");
        }
        if (!empty($feedbacks)) {
            $parts[] = count($feedbacks) . " " . (count($feedbacks) === 1 ? "feedback" : "feedbacks");
        }
        if (!empty($feedbackSets)) {
            $parts[] = count($feedbackSets) . " feedback " . (count($feedbackSets) === 1 ? "set" : "sets");
        }
        if (!empty($versions)) {
            $parts[] = count($versions) . " " . (count($versions) === 1 ? "version" : "versions");
        }

        $message .= implode(', ', $parts);

        self::notifyPreviewUsers(
            $preview->id,
            'preview_created',
            'New Preview Created',
            $message,
            "/previews/show/{$preview->slug}",
            [
                'preview_name' => $preview->name,
                'categories' => $categories,
                'feedbacks' => $feedbacks,
                'feedback_sets' => $feedbackSets,
                'versions' => $versions,
            ],
            self::$batchActorId
        );
    }

    /**
     * Create notification for users with access to the preview
     * Only notify users with notification permission
     */
    public static function notifyPreviewUsers($previewId, $type, $title, $message, $link, $data = [], $actorId = null)
    {
        // Get all users who have notification permission and preview access
        $users = User::where(function ($query) {
            $query->whereJsonContains('permissions', '/notifications')
                ->orWhereJsonContains('permissions', '*');
        })
            ->where(function ($query) {
                $query->whereJsonContains('permissions', '/previews')
                    ->orWhereJsonContains('permissions', '*');
            })
            ->get();

        foreach ($users as $user) {
            // Don't notify the actor (person who created the item)
            if ($actorId && $user->id == $actorId) {
                continue;
            }

            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'link' => $link,
                'preview_id' => $previewId,
                'actor_id' => $actorId,
                'is_read' => false,
            ]);

            // Broadcast the notification in real-time
            broadcast(new \App\Events\NotificationCreated($notification))->toOthers();
        }
    }

    /**
     * Create notification for category creation
     */
    public static function notifyCategoryCreated($category, $actorId = null)
    {
        $preview = $category->preview;

        // If batching is enabled, add to batch instead of notifying
        if (self::$batchingEnabled) {
            self::addToBatch('category', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'preview_name' => $preview->name,
            ]);
            return;
        }

        self::notifyPreviewUsers(
            $preview->id,
            'category_created',
            'New Category Added',
            "Category '{$category->name}' was added to preview '{$preview->name}'",
            "/previews/show/{$preview->slug}",
            [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'preview_name' => $preview->name,
            ],
            $actorId
        );
    }

    /**
     * Create notification for feedback creation
     */
    public static function notifyFeedbackCreated($feedback, $actorId = null)
    {
        $category = $feedback->category;
        $preview = $category->preview;

        // If batching is enabled, add to batch instead of notifying
        if (self::$batchingEnabled) {
            self::addToBatch('feedback', [
                'feedback_id' => $feedback->id,
                'feedback_name' => $feedback->name,
                'category_name' => $category->name,
                'preview_name' => $preview->name,
            ]);
            return;
        }

        self::notifyPreviewUsers(
            $preview->id,
            'feedback_created',
            'New Feedback Added',
            "Feedback '{$feedback->name}' was added to '{$category->name}' in preview '{$preview->name}'",
            "/previews/show/{$preview->slug}",
            [
                'feedback_id' => $feedback->id,
                'feedback_name' => $feedback->name,
                'category_name' => $category->name,
                'preview_name' => $preview->name,
            ],
            $actorId
        );
    }

    /**
     * Create notification for feedback set creation
     */
    public static function notifyFeedbackSetCreated($feedbackSet, $actorId = null)
    {
        $feedback = $feedbackSet->feedback;
        $category = $feedback->category;
        $preview = $category->preview;

        // If batching is enabled, add to batch instead of notifying
        if (self::$batchingEnabled) {
            self::addToBatch('feedback_set', [
                'feedback_set_id' => $feedbackSet->id,
                'feedback_set_name' => $feedbackSet->name,
                'feedback_name' => $feedback->name,
                'preview_name' => $preview->name,
            ]);
            return;
        }

        self::notifyPreviewUsers(
            $preview->id,
            'feedback_set_created',
            'New Feedback Set Added',
            "Feedback set '{$feedbackSet->name}' was added in preview '{$preview->name}'",
            "/previews/show/{$preview->slug}",
            [
                'feedback_set_id' => $feedbackSet->id,
                'feedback_set_name' => $feedbackSet->name,
                'feedback_name' => $feedback->name,
                'preview_name' => $preview->name,
            ],
            $actorId
        );
    }

    /**
     * Create notification for version creation
     */
    public static function notifyVersionCreated($version, $actorId = null)
    {
        $feedbackSet = $version->feedbackset;
        $feedback = $feedbackSet->feedback;
        $category = $feedback->category;
        $preview = $category->preview;

        // If batching is enabled, add to batch instead of notifying
        if (self::$batchingEnabled) {
            self::addToBatch('version', [
                'version_id' => $version->id,
                'version_name' => $version->name,
                'preview_name' => $preview->name,
            ]);
            return;
        }

        self::notifyPreviewUsers(
            $preview->id,
            'version_created',
            'New Version Added',
            "Version '{$version->name}' was added to preview '{$preview->name}'",
            "/previews/show/{$preview->slug}",
            [
                'version_id' => $version->id,
                'version_name' => $version->name,
                'preview_name' => $preview->name,
            ],
            $actorId
        );
    }

    /**
     * Create notification for asset creation (banner, video, gif, social)
     */
    public static function notifyAssetCreated($asset, $assetType, $actorId = null)
    {
        $version = $asset->version;
        $feedbackSet = $version->feedbackset;
        $feedback = $feedbackSet->feedback;
        $category = $feedback->category;
        $preview = $category->preview;

        $assetName = $asset->name ?? 'Untitled';

        self::notifyPreviewUsers(
            $preview->id,
            'asset_created',
            ucfirst($assetType) . ' Added',
            "New {$assetType} '{$assetName}' was added to version '{$version->name}' in preview '{$preview->name}'",
            "/previews/show/{$preview->slug}",
            [
                'asset_id' => $asset->id,
                'asset_type' => $assetType,
                'asset_name' => $assetName,
                'version_name' => $version->name,
                'preview_name' => $preview->name,
            ],
            $actorId
        );
    }

    /**
     * Create notification for feedback approval
     */
    public static function notifyFeedbackApproved($feedback, $actorId = null)
    {
        $category = $feedback->category;
        $preview = $category->preview;

        self::notifyPreviewUsers(
            $preview->id,
            'feedback_approved',
            'Feedback Approved',
            "Feedback '{$feedback->name}' was approved in '{$category->name}' for preview '{$preview->name}'",
            "/previews/show/{$preview->slug}",
            [
                'feedback_id' => $feedback->id,
                'feedback_name' => $feedback->name,
                'category_name' => $category->name,
                'preview_name' => $preview->name,
            ],
            $actorId
        );
    }

    /**
     * Create notification for feedback disapproval
     */
    public static function notifyFeedbackDisapproved($feedback, $actorId = null)
    {
        $category = $feedback->category;
        $preview = $category->preview;

        self::notifyPreviewUsers(
            $preview->id,
            'feedback_disapproved',
            'Feedback Disapproved',
            "Feedback '{$feedback->name}' was disapproved in '{$category->name}' for preview '{$preview->name}'",
            "/previews/show/{$preview->slug}",
            [
                'feedback_id' => $feedback->id,
                'feedback_name' => $feedback->name,
                'category_name' => $category->name,
                'preview_name' => $preview->name,
            ],
            $actorId
        );
    }
}
