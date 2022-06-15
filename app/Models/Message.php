<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'content',
        'receiver_id'
    ];

     /**
     *
     * @return BelongsTo
     */
    public function user():BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
