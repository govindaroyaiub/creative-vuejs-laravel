<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    /**
     * Lists every new board starts with, mapped to whether they are protected.
     *
     * Plain names — nothing re-buckets on its own. Protected lists can still be
     * renamed and reordered; they just cannot be deleted, so a board always
     * keeps its backbone.
     */
    public const DEFAULT_LISTS = [
        'Today' => true,
        'Tomorrow' => true,
        'This week' => true,
        'Later' => true,
    ];

    protected $fillable = [
        'user_id',
        'name',
        'position',
    ];

    /**
     * The board owner. Only they can rename or delete the board, or remove
     * other members.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Everyone with access, the owner included. Board membership is what grants
     * sight of the lists and cards inside.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'board_user')->withTimestamps();
    }

    public function lists()
    {
        return $this->hasMany(TaskList::class)->orderBy('position');
    }

    /**
     * Every card on the board, across all its lists.
     */
    public function tasks()
    {
        return $this->hasManyThrough(Task::class, TaskList::class, 'board_id', 'list_id');
    }

    /**
     * Restrict a query to boards the given user can see.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('members', fn ($q) => $q->where('users.id', $userId));
    }

    public function isOwnedBy($userId): bool
    {
        return (int) $this->user_id === (int) $userId;
    }
}
