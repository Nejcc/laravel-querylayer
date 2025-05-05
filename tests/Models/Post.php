<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Post extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 