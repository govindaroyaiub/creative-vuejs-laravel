<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A column on a board. Named TaskList because `list` is a PHP reserved word.
 */
class TaskList extends Model
{
    protected $table = 'task_lists';

    protected $fillable = [
        'board_id',
        'name',
        'position',
        'is_protected',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'list_id')->orderBy('position');
    }

    /**
     * Restrict a query to lists on boards the given user can see.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->whereHas(
            'board.members',
            fn ($q) => $q->where('users.id', $userId),
        );
    }
}
