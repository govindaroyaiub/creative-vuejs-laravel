<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A card. Lives in a list, which lives on a board — access follows from board
 * membership, not from the card itself.
 */
class Task extends Model
{
    protected $fillable = [
        'list_id',
        'created_by',
        'title',
        'description',
        'due_date',
        'position',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function list()
    {
        return $this->belongsTo(TaskList::class, 'list_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * People assigned to this specific card. Always a subset of the board's
     * members — being a card member is about who is doing it, not who can see
     * it.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'task_user')->withTimestamps();
    }

    /**
     * Restrict a query to cards on boards the given user can see.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->whereHas(
            'list.board.members',
            fn ($q) => $q->where('users.id', $userId),
        );
    }
}
