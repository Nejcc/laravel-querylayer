<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests\Models;

use Illuminate\Database\Eloquent\Model;

final class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'is_active',
        'role',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
