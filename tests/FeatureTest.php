<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Nejcc\LaravelQuerylayer\Tests\Models\User;
use Nejcc\LaravelQuerylayer\Tests\Repositories\UserRepository;
use ReflectionClass;
use RuntimeException;

final class FeatureTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    /** @test */
    public function it_can_use_transactions_for_create_operations(): void
    {
        $user = $this->repository->createOrFail([
            'name' => 'Transaction User',
            'email' => 'transaction@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Transaction User', $user->name);
        $this->assertEquals('transaction@example.com', $user->email);
    }

    /** @test */
    public function it_can_use_transactions_for_update_operations(): void
    {
        $user = $this->repository->create([
            'name' => 'Transaction User',
            'email' => 'transaction@example.com',
        ]);

        $result = $this->repository->updateOrFail($user->id, [
            'name' => 'Updated Transaction User',
        ]);

        $this->assertTrue($result);
        $this->assertEquals('Updated Transaction User', $user->fresh()->name);
    }

    /** @test */
    public function it_throws_exception_when_update_fails_in_transaction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to update record with ID 999');

        $this->repository->updateOrFail(999, [
            'name' => 'Will Fail',
        ]);
    }

    /** @test */
    public function it_can_execute_custom_transaction_code(): void
    {
        $result = $this->repository->transaction(function () {
            $user1 = $this->repository->create([
                'name' => 'User 1',
                'email' => 'user1@example.com',
            ]);

            $user2 = $this->repository->create([
                'name' => 'User 2',
                'email' => 'user2@example.com',
            ]);

            return [$user1->id, $user2->id];
        });

        $this->assertCount(2, $result);
        $this->assertDatabaseHas('users', ['email' => 'user1@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'user2@example.com']);
    }

    /** @test */
    public function it_can_soft_delete_records(): void
    {
        $user = $this->repository->create([
            'name' => 'Soft Delete User',
            'email' => 'soft-delete@example.com',
        ]);

        $this->repository->delete($user->id);

        // Regular find should not return soft deleted records
        $this->assertNull($this->repository->find($user->id));

        // But we should be able to find it with withTrashed
        $found = $this->repository->withTrashed()->find($user->id);
        $this->assertNotNull($found);
        $this->assertEquals('soft-delete@example.com', $found->email);

        // Check that the deleted_at column is set
        $this->assertNotNull($found->deleted_at);
    }

    /** @test */
    public function it_can_restore_soft_deleted_records(): void
    {
        $user = $this->repository->create([
            'name' => 'Restore User',
            'email' => 'restore@example.com',
        ]);

        $this->repository->delete($user->id);
        $this->assertNull($this->repository->find($user->id));

        $restored = $this->repository->restore($user->id);
        $this->assertTrue($restored);

        $found = $this->repository->find($user->id);
        $this->assertNotNull($found);
        $this->assertEquals('restore@example.com', $found->email);
        $this->assertNull($found->deleted_at);
    }

    /** @test */
    public function it_can_permanently_delete_soft_deleted_records(): void
    {
        $user = $this->repository->create([
            'name' => 'Force Delete User',
            'email' => 'force-delete@example.com',
        ]);

        $this->repository->delete($user->id);
        $this->repository->forceDelete($user->id);

        // Should not be found even with withTrashed
        $this->assertNull($this->repository->withTrashed()->find($user->id));

        // Verify in the database directly to be 100% sure
        $result = DB::table('users')->where('id', $user->id)->exists();
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_only_trashed_records(): void
    {
        // Create 3 users, delete 2 of them
        $user1 = $this->repository->create([
            'name' => 'Active User',
            'email' => 'active@example.com',
        ]);

        $user2 = $this->repository->create([
            'name' => 'Deleted User 1',
            'email' => 'deleted1@example.com',
        ]);

        $user3 = $this->repository->create([
            'name' => 'Deleted User 2',
            'email' => 'deleted2@example.com',
        ]);

        $this->repository->delete($user2->id);
        $this->repository->delete($user3->id);

        // Regular all should return only non-deleted
        $this->assertCount(1, $this->repository->all());

        // withTrashed should return all
        $this->assertCount(3, $this->repository->withTrashed()->all());

        // onlyTrashed should return only deleted
        $trashed = $this->repository->onlyTrashed()->all();
        $this->assertCount(2, $trashed);

        $emails = $trashed->pluck('email')->toArray();
        $this->assertContains('deleted1@example.com', $emails);
        $this->assertContains('deleted2@example.com', $emails);
    }

    /** @test */
    public function it_can_eager_load_relations(): void
    {
        // For a real test, we'd need a related model, but we can
        // at least test that the with property is correctly set

        // Create a record
        $user = $this->repository->create([
            'name' => 'Eager Load User',
            'email' => 'eager@example.com',
        ]);

        // Use reflection to check if the with property is correctly set
        $reflection = new ReflectionClass($this->repository);
        $withProperty = $reflection->getProperty('with');
        $withProperty->setAccessible(true);

        // Default should be empty
        $this->assertEquals([], $withProperty->getValue($this->repository));

        // Set a relation
        $this->repository->with('posts');
        $this->assertEquals(['posts'], $withProperty->getValue($this->repository));

        // Set multiple relations
        $this->repository->with(['posts', 'comments']);
        $this->assertEquals(['posts', 'comments'], $withProperty->getValue($this->repository));
    }

    /** @test */
    public function it_automatically_resets_query_scope_after_execution(): void
    {
        // Create test users
        $admin = $this->repository->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $user = $this->repository->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'role' => 'user',
        ]);

        $this->repository->delete($user->id);

        // Use reflection to check properties
        $reflection = new ReflectionClass($this->repository);
        $withProperty = $reflection->getProperty('with');
        $withProperty->setAccessible(true);
        $trashedProperty = $reflection->getProperty('trashedState');
        $trashedProperty->setAccessible(true);

        // First query with eager loading and withTrashed
        $this->repository->withTrashed()->with('posts')->all();

        // Properties should be reset after query
        $this->assertEquals([], $withProperty->getValue($this->repository));
        $this->assertEquals('none', $trashedProperty->getValue($this->repository));

        // Confirm that next query is not affected by previous scopes
        $result = $this->repository->all();

        // Only the admin should be returned (regular user is deleted)
        $this->assertCount(1, $result);
        $this->assertEquals('admin@example.com', $result->first()->email);
    }

    /** @test */
    public function it_can_manually_reset_query_scope(): void
    {
        // Use reflection to check properties
        $reflection = new ReflectionClass($this->repository);
        $withProperty = $reflection->getProperty('with');
        $withProperty->setAccessible(true);
        $trashedProperty = $reflection->getProperty('trashedState');
        $trashedProperty->setAccessible(true);

        // Set scopes
        $this->repository->withTrashed()->with('posts');

        // Properties should be set
        $this->assertEquals(['posts'], $withProperty->getValue($this->repository));
        $this->assertEquals('with', $trashedProperty->getValue($this->repository));

        // Reset manually
        $this->repository->reset();

        // Properties should be reset
        $this->assertEquals([], $withProperty->getValue($this->repository));
        $this->assertEquals('none', $trashedProperty->getValue($this->repository));
    }
}
