<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests\Repositories;

use Nejcc\LaravelQuerylayer\Repositories\BaseRepository;
use Nejcc\LaravelQuerylayer\Tests\Models\Comment;

final class CommentRepository extends BaseRepository
{
    public function getApprovedCommentsForPost(int $postId)
    {
        return $this->query()
            ->where('post_id', $postId)
            ->approved()
            ->get();
    }

    public function getRecentCommentsWithJoins(int $limit = 10)
    {
        return $this->db()
            ->select('comments.*', 'users.name as user_name', 'posts.title as post_title')
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->orderBy('comments.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getCommentCountsByUser()
    {
        return $this->db()
            ->select('user_id', $this->db()->raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->get();
    }

    protected function model(): string
    {
        return Comment::class;
    }
}
