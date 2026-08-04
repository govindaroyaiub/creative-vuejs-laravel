<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    public const STATUSES = ['todo', 'in_progress', 'done'];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    protected $fillable = [
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Everyone the task is shared with, the creator included. The pivot carries
     * `position` because each participant orders their own board.
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'task_user')
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * The user who created the task. Only they can delete it for everyone.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Restrict a query to tasks the given user takes part in. Tasks are private
     * to their participants, so every read and write path goes through this.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('participants', fn ($q) => $q->where('users.id', $userId));
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function isSharedWithOthers(): bool
    {
        return $this->participants()->count() > 1;
    }
}
