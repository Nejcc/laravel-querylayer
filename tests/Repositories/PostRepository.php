<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests\Repositories;

use Nejcc\LaravelQuerylayer\Repositories\BaseRepository;
use Nejcc\LaravelQuerylayer\Tests\Models\Post;

final class PostRepository extends BaseRepository
{
    protected function model(): string
    {
        return Post::class;
    }
    
    public function getPublishedPosts()
    {
        return $this->query()->where('is_published', true)->get();
    }
} 