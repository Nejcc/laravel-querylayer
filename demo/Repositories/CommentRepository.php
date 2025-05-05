<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Demo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Nejcc\LaravelQuerylayer\Demo\Models\Comment;
use Nejcc\LaravelQuerylayer\Repositories\BaseRepository;

final class CommentRepository extends BaseRepository
{
    /**
     * Get approved comments for a post.
     */
    public function getApprovedCommentsForPost(int $postId, ?int $perPage = null): LengthAwarePaginator
    {
        return $this->query()
            ->where('post_id', $postId)
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get recent comments across all posts using db().
     */
    public function getRecentComments(int $limit = 10): Collection
    {
        return $this->db()
            ->select('comments.*', 'users.name as user_name', 'posts.title as post_title')
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->where('comments.is_approved', true)
            ->orderBy('comments.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get pending comments for moderation using db().
     */
    public function getPendingComments(): Collection
    {
        return $this->db()
            ->select('comments.*', 'users.name as user_name', 'posts.title as post_title')
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->where('comments.is_approved', false)
            ->orderBy('comments.created_at', 'asc')
            ->get();
    }

    /**
     * Get comment statistics by user using db().
     */
    public function getCommentStatsByUser(): Collection
    {
        return $this->db()
            ->select(
                'users.id',
                'users.name',
                $this->db()->raw('COUNT(comments.id) as total_comments'),
                $this->db()->raw('SUM(CASE WHEN comments.is_approved = 1 THEN 1 ELSE 0 END) as approved_comments'),
                $this->db()->raw('AVG(CASE WHEN comments.is_approved = 1 THEN 1 ELSE 0 END) as approval_rate')
            )
            ->rightJoin('users', 'comments.user_id', '=', 'users.id')
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_comments', 'desc')
            ->get();
    }

    /**
     * Find comments with specific keywords using db().
     */
    public function findCommentsByKeywords(array $keywords): Collection
    {
        $query = $this->db()
            ->select('comments.*', 'users.name as user_name', 'posts.title as post_title')
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->join('posts', 'comments.post_id', '=', 'posts.id');

        // Build the keyword search condition
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $index => $keyword) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $q->$method('comments.content', 'like', "%{$keyword}%");
            }
        });

        return $query->orderBy('comments.created_at', 'desc')->get();
    }

    /**
     * Approve comments in bulk using db().
     */
    public function approveCommentsBulk(array $commentIds): int
    {
        return $this->db()
            ->whereIn('id', $commentIds)
            ->update(['is_approved' => true]);
    }

    /**
     * Define which model the repository works with.
     */
    protected function model(): string
    {
        return Comment::class;
    }
}
