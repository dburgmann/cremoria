<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender',
        'receiver',
        'title',
        'text',
        'unread',
    ];

    protected $casts = [
        'unread' => 'boolean',
    ];

    public function receiverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver');
    }
}
