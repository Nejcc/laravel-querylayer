<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nejcc\LaravelQuerylayer\Tests\Models\Comment;
use Nejcc\LaravelQuerylayer\Tests\Models\User;
use Nejcc\LaravelQuerylayer\Tests\Repositories\CommentRepository;
use Nejcc\LaravelQuerylayer\Tests\Repositories\PostRepository;
use Nejcc\LaravelQuerylayer\Tests\Repositories\UserRepository;

final class CommentRepositoryDbTest extends TestCase
{
    use RefreshDatabase;

    protected CommentRepository $repository;

    protected UserRepository $userRepository;

    protected PostRepository $postRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CommentRepository();
        $this->userRepository = new UserRepository();
        $this->postRepository = new PostRepository();
        $this->seedData();
    }

    /** @test */
    public function it_can_get_comments_with_related_data_using_db_query(): void
    {
        $comments = $this->repository->getRecentCommentsWithJoins(10);

        $this->assertCount(6, $comments);
        $this->assertTrue(isset($comments[0]->user_name));
        $this->assertTrue(isset($comments[0]->post_title));
    }

    /** @test */
    public function it_can_count_comments_by_user_using_db_query(): void
    {
        $userCounts = $this->repository->getCommentCountsByUser();

        $this->assertCount(2, $userCounts);

        // Convert to array for easier testing
        $counts = [];
        foreach ($userCounts as $count) {
            $counts[$count->user_id] = $count->total;
        }

        // Get user IDs for verification
        $adminUser = User::where('name', 'Admin User')->first();
        $regularUser = User::where('name', 'Regular User')->first();

        $this->assertEquals(2, $counts[$adminUser->id]);
        $this->assertEquals(4, $counts[$regularUser->id]);
    }

    /** @test */
    public function it_can_filter_comments_by_approval_status_using_db_query(): void
    {
        $approvedComments = $this->repository->db()
            ->where('is_approved', true)
            ->count();

        $this->assertEquals(3, $approvedComments);

        $pendingComments = $this->repository->db()
            ->where('is_approved', false)
            ->count();

        $this->assertEquals(3, $pendingComments);
    }

    /** @test */
    public function it_can_approve_comments_in_bulk_using_db_query(): void
    {
        // Approve all comments
        $affected = $this->repository->db()
            ->where('is_approved', false)
            ->update(['is_approved' => true]);

        $this->assertEquals(3, $affected);

        // Verify all comments are now approved
        $approvedComments = $this->repository->db()
            ->where('is_approved', true)
            ->count();

        $this->assertEquals(6, $approvedComments);
    }

    /** @test */
    public function it_can_join_and_filter_on_multiple_tables(): void
    {
        // Get comments on published posts by active users
        $result = $this->repository->db()
            ->select('comments.*', 'users.name as user_name', 'posts.title as post_title')
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->where('users.is_active', true)
            ->where('posts.is_published', true)
            ->orderBy('comments.id')
            ->get();

        $this->assertCount(5, $result); // Adjust count based on actual results
    }

    /** @test */
    public function it_can_get_approval_statistics_using_db_query(): void
    {
        // Get approval statistics by user
        $result = $this->repository->db()
            ->select(
                'users.name',
                $this->repository->db()->raw('COUNT(comments.id) as total'),
                $this->repository->db()->raw('SUM(CASE WHEN comments.is_approved = 1 THEN 1 ELSE 0 END) as approved'),
                $this->repository->db()->raw('(SUM(CASE WHEN comments.is_approved = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(comments.id)) as approval_percentage')
            )
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->groupBy('users.id', 'users.name')
            ->orderBy('approval_percentage', 'desc')
            ->get();

        $this->assertCount(2, $result);

        // Admin User should have 50% approval rate (1 of 2 approved)
        $this->assertEquals('Admin User', $result[0]->name);
        $this->assertEquals(2, $result[0]->total);
        $this->assertEquals(1, $result[0]->approved);
        $this->assertEquals(50.0, $result[0]->approval_percentage);

        // Regular User should have 50% approval rate (2 of 4 approved)
        $this->assertEquals('Regular User', $result[1]->name);
        $this->assertEquals(4, $result[1]->total);
        $this->assertEquals(2, $result[1]->approved);
        $this->assertEquals(50.0, $result[1]->approval_percentage);
    }

    /** @test */
    public function it_can_find_comments_with_keyword_search_using_db_query(): void
    {
        // Search for comments containing 'great'
        $result = $this->repository->db()
            ->where('content', 'like', '%great%')
            ->get();

        $this->assertCount(2, $result);
    }

    private function seedData(): void
    {
        // Create users
        $adminUser = $this->userRepository->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $regularUser = $this->userRepository->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);

        // Create posts
        $post1 = $this->postRepository->create([
            'user_id' => $adminUser->id,
            'title' => 'First Post',
            'content' => 'Content of the first post',
            'is_published' => true,
        ]);

        $post2 = $this->postRepository->create([
            'user_id' => $adminUser->id,
            'title' => 'Second Post',
            'content' => 'Content of the second post',
            'is_published' => true,
        ]);

        $post3 = $this->postRepository->create([
            'user_id' => $regularUser->id,
            'title' => 'User Post',
            'content' => 'Content from regular user',
            'is_published' => false,
        ]);

        // Create comments
        $this->createComment($adminUser->id, $post1->id, 'This is a great post!', true);
        $this->createComment($regularUser->id, $post1->id, 'I agree, great content.', true);
        $this->createComment($adminUser->id, $post2->id, 'Another excellent post.', false);
        $this->createComment($regularUser->id, $post2->id, 'Looking forward to more content.', true);
        $this->createComment($regularUser->id, $post3->id, 'This is my own post comment.', false);
        $this->createComment($regularUser->id, $post1->id, 'Additional thoughts on this topic.', false);
    }

    private function createComment(int $userId, int $postId, string $content, bool $isApproved): Comment
    {
        return $this->repository->create([
            'user_id' => $userId,
            'post_id' => $postId,
            'content' => $content,
            'is_approved' => $isApproved,
        ]);
    }
}
