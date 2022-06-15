<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, Searchable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'username',
        'avatar',
        'facebook_id',
        'google_id',
        'github_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

     /**
     *
     * @return HasMany
     */
    public function userFriends(): HasMany
    {
        return $this->hasMany(Friend::class, "user_id");
    }

     /**
     *
     * @return HasMany
     */
    public function friendsUser(): HasMany
    {
        return $this->hasMany(Friend::class, "friend_id");
    }

     /**
     *
     * @return HasMany
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, "sender_id", "id");
    }

     /**
     *
     * @return HasMany
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, "receiver_id", "id");
    }

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
     * @return BelongsToMany
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

     /**
     *
     * @return BelongsToMany
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class);
    }

     /**
     *
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
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

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            DB::transaction(function () use ($user) {
                $exercises = $user->exercises;
                foreach ($exercises as $exercise) {
                    $exercise->comments()->delete();
                    $exercise->submissions()->delete();
                    $exercise->users()->detach();
                }

                $user->comments()->delete();
                $user->friendsUser()->delete();
                $user->userFriends()->delete();
                $user->sentMessages()->delete();
                $user->receivedMessages()->delete();
                $posts = $user->posts;
                foreach ($posts as $post) {
                    $post->comments()->delete();
                    $post->delete();
                }
                $user->groups()->detach();
            });
        });
    }
}
