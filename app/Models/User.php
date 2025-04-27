<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Designation;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'designation',
        'role',         // ✅ added
        'permissions',  // ✅ added
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array', // ✅ important!
        ];
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation');
    }

    // ✅ NEW: Helper to check if user can access a route
    public function canAccess($route)
    {
        if (is_array($this->permissions)) {
            return in_array($route, $this->permissions);
        }
        return false;
    }
}