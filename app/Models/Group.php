<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Group extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'privacy',
        'description',
        'slug',
        'avatar',
        'creator_id'
    ];

    protected $hidden = [
        'join_code'
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
     * @return HasOne
     */
    public function creator(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'creator_id');
    }

    /**
     *
     * @return HasManyThrough
     */
    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(Post::class, User::class);
    }

    /**
     *
     * @return HasManyThrough
     */
    public function exercises(): HasManyThrough
    {
        return $this->hasManyThrough(Exercise::class, Channel::class);
    }

    /**
     *
     * @return HasMany
     */
    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    /**
     *
     * @return HasMany
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Applies Scout Extended default transformations:
        $array = $this->transform($array);

        // Add an extra attribute:
        $array['added_month'] = substr($array['created_at'], 0, 7);

        return $array;
    }
}
