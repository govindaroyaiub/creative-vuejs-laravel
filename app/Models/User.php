<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Designation;
use App\Models\Client;

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
        'client_id',  // ✅ added
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

    public function client()
    {
        return $this->belongsTo(Client::class);
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