<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel_id',
        'title',
        'description',
        'deadline',
        'file_path'
    ];

    /**
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     *
     * @return BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     *
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ExerciseComment::class);
    }

    /**
     *
     * @return HasMany
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ExerciseSubmission::class);
    }
}
