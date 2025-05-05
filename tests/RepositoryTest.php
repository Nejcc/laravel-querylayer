<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Nejcc\LaravelQuerylayer\Tests\Models\User;
use Nejcc\LaravelQuerylayer\Tests\Repositories\UserRepository;

final class RepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    /** @test */
    public function it_can_create_a_record(): void
    {
        $user = $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
            'role' => 'user',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue($user->is_active);
        $this->assertEquals('user', $user->role);
    }

    /** @test */
    public function it_can_find_a_record_by_id(): void
    {
        $created = $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $found = $this->repository->find($created->id);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($created->id, $found->id);
    }

    /** @test */
    public function it_can_find_a_record_by_column(): void
    {
        $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $found = $this->repository->findBy('email', 'john@example.com');

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals('john@example.com', $found->email);
    }

    /** @test */
    public function it_can_get_all_records(): void
    {
        $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->repository->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $all = $this->repository->all();

        $this->assertInstanceOf(Collection::class, $all);
        $this->assertCount(2, $all);
    }

    /** @test */
    public function it_can_update_a_record(): void
    {
        $user = $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $updated = $this->repository->update($user->id, [
            'name' => 'John Updated',
        ]);

        $this->assertTrue($updated);
        $this->assertEquals('John Updated', $user->fresh()->name);
    }

    /** @test */
    public function it_can_delete_a_record(): void
    {
        $user = $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $deleted = $this->repository->delete($user->id);

        $this->assertTrue($deleted);
        $this->assertNull($this->repository->find($user->id));
    }

    /** @test */
    public function it_can_get_paginated_results(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->repository->create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
            ]);
        }

        $paginated = $this->repository->paginate(10);

        $this->assertCount(10, $paginated->items());
        $this->assertEquals(20, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
    }

    /** @test */
    public function it_can_get_records_where_conditions_match(): void
    {
        $this->repository->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->repository->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);

        $admins = $this->repository->where(['role' => 'admin']);

        $this->assertInstanceOf(Collection::class, $admins);
        $this->assertCount(1, $admins);
        $this->assertEquals('admin@example.com', $admins->first()->email);
    }

    /** @test */
    public function it_can_use_custom_query_methods(): void
    {
        $this->repository->create([
            'name' => 'Active Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->repository->create([
            'name' => 'Inactive Admin',
            'email' => 'inactive-admin@example.com',
            'role' => 'admin',
            'is_active' => false,
        ]);

        $activeAdmins = $this->repository->getActiveAdminsQuery()->get();

        $this->assertCount(1, $activeAdmins);
        $this->assertEquals('admin@example.com', $activeAdmins->first()->email);
    }
}
