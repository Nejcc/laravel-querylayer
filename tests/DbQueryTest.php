<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Nejcc\LaravelQuerylayer\Tests\Models\Comment;
use Nejcc\LaravelQuerylayer\Tests\Models\User;
use Nejcc\LaravelQuerylayer\Tests\Repositories\CommentRepository;
use Nejcc\LaravelQuerylayer\Tests\Repositories\PostRepository;
use Nejcc\LaravelQuerylayer\Tests\Repositories\UserRepository;

final class DbQueryTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $userRepository;

    protected PostRepository $postRepository;

    protected CommentRepository $commentRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = new UserRepository();
        $this->postRepository = new PostRepository();
        $this->commentRepository = new CommentRepository();

        // Seed some test data
        $this->seedTestData();
    }

    /** @test */
    public function it_can_perform_raw_db_queries(): void
    {
        // Test basic db query
        $result = $this->userRepository->db()->where('role', 'admin')->get();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('Admin User', $result[0]->name);
    }

    /** @test */
    public function it_can_perform_group_by_queries(): void
    {
        // Test grouping and aggregation
        $result = $this->userRepository->db()
            ->select('role', $this->userRepository->db()->raw('COUNT(*) as count'))
            ->groupBy('role')
            ->orderBy('count', 'desc')
            ->get();

        $this->assertCount(2, $result);
        $this->assertEquals('user', $result[0]->role);
        $this->assertEquals(3, $result[0]->count);
        $this->assertEquals('admin', $result[1]->role);
        $this->assertEquals(2, $result[1]->count);
    }

    /** @test */
    public function it_can_perform_join_queries(): void
    {
        // Test join query
        $result = $this->postRepository->db()
            ->select('posts.*', 'users.name as author_name')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->where('posts.is_published', true)
            ->orderBy('posts.id')
            ->get();

        $this->assertCount(3, $result);
        $this->assertEquals('Admin User', $result[0]->author_name);
        $this->assertEquals('First Post', $result[0]->title);
    }

    /** @test */
    public function it_can_perform_complex_aggregation_queries(): void
    {
        // Test complex aggregation
        $result = $this->commentRepository->db()
            ->select(
                'users.name',
                $this->commentRepository->db()->raw('COUNT(comments.id) as comment_count')
            )
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->groupBy('users.id', 'users.name')
            ->orderBy('comment_count', 'desc')
            ->get();

        $this->assertCount(3, $result);
        $this->assertEquals('Regular User 1', $result[0]->name);
        $this->assertEquals(5, $result[0]->comment_count);
    }

    /** @test */
    public function it_can_perform_having_clause_queries(): void
    {
        // Test having clause
        $result = $this->userRepository->db()
            ->select('users.id', 'users.name')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->groupBy('users.id', 'users.name')
            ->having($this->userRepository->db()->raw('COUNT(posts.id)'), '>=', 2)
            ->get();

        $this->assertCount(2, $result); // Only 2 users have 2 or more posts
        // The names should include Admin User and Regular User 1
        $this->assertTrue(in_array('Admin User', [$result[0]->name, $result[1]->name]));
        $this->assertTrue(in_array('Regular User 1', [$result[0]->name, $result[1]->name]));
    }

    /** @test */
    public function it_can_perform_update_via_db_query(): void
    {
        // Test update via db()
        $affected = $this->commentRepository->db()
            ->where('is_approved', false)
            ->update(['is_approved' => true]);

        $this->assertEquals(5, $affected); // 5 comments were not approved

        $allApproved = $this->commentRepository->db()
            ->where('is_approved', true)
            ->count();

        $this->assertEquals(10, $allApproved); // Now all 10 comments should be approved
    }

    /** @test */
    public function it_can_perform_complex_joins_with_multiple_tables(): void
    {
        // Test complex joins with multiple tables
        $result = $this->commentRepository->getRecentCommentsWithJoins();

        $this->assertCount(10, $result);
        $this->assertTrue(isset($result[0]->user_name));
        $this->assertTrue(isset($result[0]->post_title));
    }

    /** @test */
    public function it_can_perform_subqueries(): void
    {
        // Test subquery
        $result = $this->userRepository->db()
            ->whereIn('id', function ($query) {
                $query->select('user_id')
                    ->from('comments')
                    ->where('is_approved', true)
                    ->groupBy('user_id')
                    ->havingRaw('COUNT(*) > 3');
            })
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Regular User 1', $result[0]->name);
    }

    /**
     * Seed test data for the db tests
     */
    private function seedTestData(): void
    {
        // Create users
        $adminUser = $this->userRepository->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $inactiveAdmin = $this->userRepository->create([
            'name' => 'Inactive Admin',
            'email' => 'inactive-admin@example.com',
            'role' => 'admin',
            'is_active' => false,
        ]);

        $regularUser1 = $this->userRepository->create([
            'name' => 'Regular User 1',
            'email' => 'user1@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);

        $regularUser2 = $this->userRepository->create([
            'name' => 'Regular User 2',
            'email' => 'user2@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);

        $inactiveUser = $this->userRepository->create([
            'name' => 'Inactive User',
            'email' => 'inactive-user@example.com',
            'role' => 'user',
            'is_active' => false,
        ]);

        // Create posts
        $adminPost1 = $this->postRepository->create([
            'user_id' => $adminUser->id,
            'title' => 'First Post',
            'content' => 'Content of the first post',
            'is_published' => true,
        ]);

        $adminPost2 = $this->postRepository->create([
            'user_id' => $adminUser->id,
            'title' => 'Second Post',
            'content' => 'Content of the second post',
            'is_published' => true,
        ]);

        $regularUserPost1 = $this->postRepository->create([
            'user_id' => $regularUser1->id,
            'title' => 'User Post 1',
            'content' => 'Content of the user post 1',
            'is_published' => true,
        ]);

        $regularUserPost2 = $this->postRepository->create([
            'user_id' => $regularUser1->id,
            'title' => 'User Post 2',
            'content' => 'Content of the user post 2',
            'is_published' => false,
        ]);

        $regularUser2Post = $this->postRepository->create([
            'user_id' => $regularUser2->id,
            'title' => 'Another User Post',
            'content' => 'Content of another user post',
            'is_published' => false,
        ]);

        // Create comments
        $this->createComment($adminUser->id, $adminPost1->id, 'Admin comment on own post', true);
        $this->createComment($regularUser1->id, $adminPost1->id, 'User comment on admin post 1', true);
        $this->createComment($regularUser2->id, $adminPost1->id, 'Another user comment on admin post 1', false);

        $this->createComment($regularUser1->id, $adminPost2->id, 'User comment on admin post 2', true);
        $this->createComment($regularUser1->id, $regularUserPost1->id, 'User comment on own post', true);

        $this->createComment($adminUser->id, $regularUserPost1->id, 'Admin comment on user post', false);
        $this->createComment($regularUser2->id, $regularUserPost1->id, 'Another user comment on user post', false);

        $this->createComment($regularUser1->id, $regularUserPost2->id, 'User comment on own unpublished post', true);
        $this->createComment($regularUser1->id, $regularUser2Post->id, 'User comment on another user post', false);

        $this->createComment($regularUser2->id, $regularUserPost2->id, 'Another user comment on unpublished post', false);
    }

    /**
     * Helper to create a comment
     */
    private function createComment(int $userId, int $postId, string $content, bool $isApproved): Comment
    {
        return $this->commentRepository->create([
            'user_id' => $userId,
            'post_id' => $postId,
            'content' => $content,
            'is_approved' => $isApproved,
        ]);
    }
}
