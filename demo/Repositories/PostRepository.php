<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Demo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Nejcc\LaravelQuerylayer\Demo\Models\Post;
use Nejcc\LaravelQuerylayer\Repositories\BaseRepository;

final class PostRepository extends BaseRepository
{
    /**
     * Get published posts with pagination.
     */
    public function getPublishedPosts(?int $perPage = null): LengthAwarePaginator
    {
        return $this->query()
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get recent posts with comments count using db().
     */
    public function getRecentPostsWithCommentsCount(int $days = 7): Collection
    {
        return $this->db()
            ->select('posts.*', $this->db()->raw('COUNT(comments.id) as comments_count'))
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->where('posts.is_published', true)
            ->where('posts.published_at', '>=', now()->subDays($days))
            ->groupBy('posts.id')
            ->orderBy('posts.published_at', 'desc')
            ->get();
    }

    /**
     * Find most commented posts using db().
     */
    public function getMostCommentedPosts(int $limit = 10): Collection
    {
        return $this->db()
            ->select('posts.*', $this->db()->raw('COUNT(comments.id) as comments_count'))
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->where('posts.is_published', true)
            ->groupBy('posts.id')
            ->having('comments_count', '>', 0)
            ->orderBy('comments_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Find posts by user with approval statistics using db().
     */
    public function getPostsWithApprovalStats(int $userId): Collection
    {
        return $this->db()
            ->select(
                'posts.*',
                $this->db()->raw('COUNT(comments.id) as total_comments'),
                $this->db()->raw('SUM(CASE WHEN comments.is_approved = 1 THEN 1 ELSE 0 END) as approved_comments'),
                $this->db()->raw('SUM(CASE WHEN comments.is_approved = 0 THEN 1 ELSE 0 END) as pending_comments')
            )
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->where('posts.user_id', $userId)
            ->groupBy('posts.id')
            ->orderBy('posts.published_at', 'desc')
            ->get();
    }

    /**
     * Advanced search with complex conditions using db().
     */
    public function advancedSearch(array $filters): Collection
    {
        $query = $this->db();

        // Start with base selection
        $query->select('posts.*');

        // Apply user filter if specified
        if (isset($filters['user_id'])) {
            $query->where('posts.user_id', $filters['user_id']);
        }

        // Apply title search if specified
        if (isset($filters['title'])) {
            $query->where('posts.title', 'like', "%{$filters['title']}%");
        }

        // Apply date range filter if specified
        if (isset($filters['date_from'])) {
            $query->where('posts.published_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('posts.published_at', '<=', $filters['date_to']);
        }

        // Apply has_comments filter if specified
        if (isset($filters['has_comments']) && $filters['has_comments']) {
            $query->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
                ->groupBy('posts.id')
                ->having($this->db()->raw('COUNT(comments.id)'), '>', 0);
        }

        // Only include published posts unless specified
        if (! isset($filters['include_unpublished']) || ! $filters['include_unpublished']) {
            $query->where('posts.is_published', true);
        }

        return $query->orderBy('posts.published_at', 'desc')->get();
    }

    /**
     * Define which model the repository works with.
     */
    protected function model(): string
    {
        return Post::class;
    }
}
