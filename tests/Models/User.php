<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class User extends Model
{
    use SoftDeletes;
    
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
}
