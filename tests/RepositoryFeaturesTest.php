<?php

namespace Nejcc\LaravelQuerylayer\Tests;

use Illuminate\Support\Facades\Cache;
use Nejcc\LaravelQuerylayer\Tests\Models\User;
use Nejcc\LaravelQuerylayer\Tests\Repositories\UserRepository;

class RepositoryFeaturesTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    public function test_caching_works_for_find()
    {
        // Create a test user
        $user = $this->repository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Enable caching
        $this->repository->cache();

        // First call should hit the database
        $result1 = $this->repository->find($user->id);
        $this->assertNotNull($result1);

        // Second call should use cache
        $result2 = $this->repository->find($user->id);
        $this->assertNotNull($result2);

        // Verify cache was used
        $cacheKey = "repository_users_find_" . md5(serialize(['id' => $user->id]));
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_complex_where_clauses()
    {
        // Create test users
        $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
        ]);

        $this->repository->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'is_active' => false,
        ]);

        // Test OR condition
        $results = $this->repository
            ->whereCondition(['name' => 'John Doe'])
            ->orWhere(['email' => 'jane@example.com'])
            ->get();

        $this->assertCount(2, $results);

        // Test nested where
        $results = $this->repository
            ->whereNested(function ($query) {
                $query->where('name', 'John Doe')
                    ->orWhere('name', 'Jane Doe');
            })
            ->whereCondition(['is_active' => true])
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->name);
    }

    public function test_query_scopes()
    {
        // Create test users
        $this->repository->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->repository->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'role' => 'user',
        ]);

        // Test scope
        $results = $this->repository
            ->scope('role', 'admin')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('admin', $results->first()->role);
    }

    public function test_raw_queries()
    {
        // Create test users
        $this->repository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Test raw where
        $results = $this->repository
            ->whereRaw('LOWER(name) = ?', ['test user'])
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Test User', $results->first()->name);
    }
} 