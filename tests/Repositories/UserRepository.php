<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Nejcc\LaravelQuerylayer\Repositories\BaseRepository;
use Nejcc\LaravelQuerylayer\Tests\Models\User;

final class UserRepository extends BaseRepository
{
    public function getActiveAdminsQuery(): Builder
    {
        return $this->query()
            ->where('is_active', true)
            ->where('role', 'admin');
    }

    protected function model(): string
    {
        return User::class;
    }
}
