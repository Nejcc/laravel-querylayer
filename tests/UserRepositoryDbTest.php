<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Nejcc\LaravelQuerylayer\Tests\Models\User;
use Nejcc\LaravelQuerylayer\Tests\Repositories\UserRepository;

final class UserRepositoryDbTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
        $this->seedUsers();
    }

    /** @test */
    public function it_can_get_users_by_role_with_db_query(): void
    {
        $admins = $this->repository->db()
            ->where('role', 'admin')
            ->get();

        $this->assertInstanceOf(Collection::class, $admins);
        $this->assertCount(2, $admins);
        $this->assertEquals('admin@example.com', $admins[0]->email);
    }

    /** @test */
    public function it_can_count_users_by_role_with_db_query(): void
    {
        $roleCounts = $this->repository->db()
            ->select('role', $this->repository->db()->raw('COUNT(*) as count'))
            ->groupBy('role')
            ->orderBy('count', 'desc')
            ->get();

        $this->assertCount(2, $roleCounts);
        $this->assertEquals('user', $roleCounts[0]->role);
        $this->assertEquals(3, $roleCounts[0]->count);
    }

    /** @test */
    public function it_can_search_users_with_dynamic_conditions_using_db_query(): void
    {
        // Test filtering by name
        $users = $this->repository->db()
            ->where('name', 'like', '%Admin%')
            ->get();

        $this->assertCount(2, $users);
        $this->assertTrue(in_array('Admin User', [$users[0]->name, $users[1]->name]));
        $this->assertTrue(in_array('Inactive Admin', [$users[0]->name, $users[1]->name]));

        // Test filtering by multiple conditions
        $activeAdmins = $this->repository->db()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->get();

        $this->assertCount(1, $activeAdmins);
        $this->assertEquals('Admin User', $activeAdmins[0]->name);
    }

    /** @test */
    public function it_can_perform_bulk_updates_with_db_query(): void
    {
        // Deactivate all admin users
        $affected = $this->repository->db()
            ->where('role', 'admin')
            ->update(['is_active' => false]);

        $this->assertEquals(2, $affected);

        // Verify all admins are now inactive
        $inactiveAdmins = $this->repository->db()
            ->where('role', 'admin')
            ->where('is_active', false)
            ->count();

        $this->assertEquals(2, $inactiveAdmins);
    }

    /** @test */
    public function it_can_find_users_without_related_records_using_db_query(): void
    {
        // Find users with no posts using a LEFT JOIN
        $usersWithNoPosts = $this->repository->db()
            ->select('users.*')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->whereNull('posts.id')
            ->get();

        $this->assertCount(5, $usersWithNoPosts);

        // Verify that these users are in the result set
        $userEmails = $usersWithNoPosts->pluck('email')->toArray();
        $this->assertTrue(in_array('inactive@example.com', $userEmails));
        $this->assertTrue(in_array('inactive-user@example.com', $userEmails));
    }

    /** @test */
    public function it_can_perform_advanced_joins_and_aggregations(): void
    {
        // Create some posts for our test users
        $adminUser = User::where('name', 'Admin User')->first();
        $regularUser = User::where('name', 'Regular User 1')->first();

        $this->createPost($adminUser->id, 'Admin Post 1', true);
        $this->createPost($adminUser->id, 'Admin Post 2', true);
        $this->createPost($regularUser->id, 'User Post', true);

        // Get users with post count using a join and aggregation
        $usersWithPostCount = $this->repository->db()
            ->select('users.*', $this->repository->db()->raw('COUNT(posts.id) as post_count'))
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->groupBy('users.id')
            ->orderBy('post_count', 'desc')
            ->get();

        // First record should be the Admin User with 2 posts
        $this->assertEquals('Admin User', $usersWithPostCount[0]->name);
        $this->assertEquals(2, $usersWithPostCount[0]->post_count);

        // Second record should be Regular User 1 with 1 post
        $this->assertEquals('Regular User 1', $usersWithPostCount[1]->name);
        $this->assertEquals(1, $usersWithPostCount[1]->post_count);

        // The remaining users should have 0 posts
        $this->assertEquals(0, $usersWithPostCount[2]->post_count);
    }

    private function seedUsers(): void
    {
        $this->repository->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->repository->create([
            'name' => 'Inactive Admin',
            'email' => 'inactive@example.com',
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->repository->create([
            'name' => 'Regular User 1',
            'email' => 'user1@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->repository->create([
            'name' => 'Regular User 2',
            'email' => 'user2@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->repository->create([
            'name' => 'Inactive User',
            'email' => 'inactive-user@example.com',
            'role' => 'user',
            'is_active' => false,
        ]);
    }

    private function createPost(int $userId, string $title, bool $isPublished): void
    {
        Models\Post::create([
            'user_id' => $userId,
            'title' => $title,
            'content' => 'Test content for '.$title,
            'is_published' => $isPublished,
        ]);
    }
}
