<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests;

use Nejcc\LaravelQuerylayer\Tests\Models\User;

final class ModelInterfaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        User::getRepository()->deleteWhere([]);
        parent::tearDown();
    }

    public function test_model_interface_methods()
    {
        // Test create
        $user = User::createRecord([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_active' => true,
            'role' => 'user',
        ]);

        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->name);

        // Test findById
        $foundUser = User::findById($user->id);
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);

        // Test findByColumn
        $foundByEmail = User::findByColumn('email', 'test@example.com');
        $this->assertNotNull($foundByEmail);
        $this->assertEquals($user->id, $foundByEmail->id);

        // Test update
        $updated = User::updateRecord($user->id, ['name' => 'Updated User']);
        $this->assertTrue($updated);

        $updatedUser = User::findById($user->id);
        $this->assertEquals('Updated User', $updatedUser->name);

        // Test getWhere
        $users = User::getWhere(['role' => 'user']);
        $this->assertCount(1, $users);
        $this->assertEquals($user->id, $users->first()->id);

        // Test getAll
        $allUsers = User::getAll();
        $this->assertCount(1, $allUsers);
        $this->assertEquals($user->id, $allUsers->first()->id);

        // Test getPaginated
        $paginated = User::getPaginated(10);
        $this->assertCount(1, $paginated);
        $this->assertEquals($user->id, $paginated->first()->id);

        // Test delete
        $deleted = User::deleteRecord($user->id);
        $this->assertTrue($deleted);

        $deletedUser = User::findById($user->id);
        $this->assertNull($deletedUser);
    }

    public function test_model_query_builder()
    {
        // Create test users
        User::createRecord([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'is_active' => true,
            'role' => 'admin',
        ]);

        User::createRecord([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'is_active' => false,
            'role' => 'user',
        ]);

        // Test query builder
        $query = User::getQuery();
        $this->assertNotNull($query);

        // Test with query builder
        $activeUsers = User::getQuery()
            ->where('is_active', true)
            ->get();
        $this->assertCount(1, $activeUsers);
        $this->assertEquals('User 1', $activeUsers->first()->name);

        // Test with repository methods
        $adminUsers = User::getRepository()
            ->whereCondition(['role' => 'admin'])
            ->get();
        $this->assertCount(1, $adminUsers);
        $this->assertEquals('User 1', $adminUsers->first()->name);
    }
}
