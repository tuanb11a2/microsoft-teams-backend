<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'name',
        'type',
        'deadline',
        'user_id',
        'priority',
        'group_id',
    ];

     /**
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

     /**
     *
     * @return BelongsTo
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
