<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
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

            Notification::create([
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
        }
    }

    /**
     * Create notification for category creation
     */
    public static function notifyCategoryCreated($category, $actorId = null)
    {
        $preview = $category->preview;

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
}
