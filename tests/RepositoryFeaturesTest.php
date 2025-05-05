<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests;

use Illuminate\Support\Facades\Cache;
use Nejcc\LaravelQuerylayer\Tests\Models\User;
use Nejcc\LaravelQuerylayer\Tests\Repositories\UserRepository;

final class RepositoryFeaturesTest extends TestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository(new User());
    }

    protected function tearDown(): void
    {
        $this->repository->deleteWhere([]);
        parent::tearDown();
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
        $cacheKey = 'repository_users_find_'.md5(serialize(['id' => $user->id]));
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

    public function test_batch_operations()
    {
        // Test bulk insert
        $records = [
            ['name' => 'User 1', 'email' => 'user1@example.com'],
            ['name' => 'User 2', 'email' => 'user2@example.com'],
            ['name' => 'User 3', 'email' => 'user3@example.com'],
        ];

        $this->assertTrue($this->repository->insert($records));
        $this->assertCount(3, $this->repository->all());

        // Test insert with IDs
        $newRecords = [
            ['name' => 'User 4', 'email' => 'user4@example.com'],
            ['name' => 'User 5', 'email' => 'user5@example.com'],
        ];

        $ids = $this->repository->insertGetIds($newRecords);
        $this->assertCount(2, $ids);
        $this->assertCount(5, $this->repository->all());

        // Test bulk update
        $affected = $this->repository->updateWhere(
            ['name' => 'Updated User'],
            ['email' => 'user1@example.com']
        );
        $this->assertEquals(1, $affected);
        $this->assertEquals(
            'Updated User',
            $this->repository->findBy('email', 'user1@example.com')->name
        );

        // Test bulk delete
        $deleted = $this->repository->deleteWhere(['email' => 'user2@example.com']);
        $this->assertEquals(1, $deleted);
        $this->assertCount(4, $this->repository->all());
    }

    public function test_chunk_operations()
    {
        // Create test data
        $records = [];
        for ($i = 1; $i <= 10; $i++) {
            $records[] = [
                'name' => "User $i",
                'email' => "user$i@example.com",
            ];
        }
        $this->repository->insert($records);

        // Test chunking
        $processed = 0;
        $this->repository->chunk(3, function ($users) use (&$processed) {
            $processed += $users->count();
        });
        $this->assertEquals(10, $processed);

        // Test chunking by ID
        $processed = 0;
        $this->repository->chunkById(3, function ($users) use (&$processed) {
            $processed += $users->count();
        });
        $this->assertEquals(10, $processed);
    }
}
