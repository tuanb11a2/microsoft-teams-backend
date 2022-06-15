<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'group_id',
        'slug'
    ];

    /**
     *
     * @return HasMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     *
     * @return HasMany
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function submissions(): HasManyThrough
    {
        return $this->hasManyThrough(ExerciseSubmission::class, Exercise::class);
    }

    /**
     * @return BelongsTo
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
