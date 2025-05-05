<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nejcc\LaravelQuerylayer\Contracts\ModelInterface;
use Nejcc\LaravelQuerylayer\Tests\Repositories\UserRepository;
use Nejcc\LaravelQuerylayer\Traits\HasRepository;

/**
 * @implements ModelInterface<User>
 */
final class User extends Model implements ModelInterface
{
    use HasRepository, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'is_active',
        'role',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include users with a specific role.
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Get the repository class for this model
     *
     * @return class-string<UserRepository>
     */
    protected static function getRepositoryClass(): string
    {
        return UserRepository::class;
    }
}
