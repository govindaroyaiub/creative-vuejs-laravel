<?php

// app/Models/FileTransfer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileTransfer extends Model
{
    public $timestamps = true;
    protected $fillable = ['name', 'client', 'user_id', 'file_path']; // Ensure 'user_id' is fillable

    // Define the relationship
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // 'user_id' should be an integer
    }
}