<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_id',
        'user_id',
        'content',
    ];

    /**
     *
     * @return BelongsTo
     */
    public function exercises(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
