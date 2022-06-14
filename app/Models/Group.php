<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * @return HasOne
     */
    public function creator(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'creator_id');
    }

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
}
