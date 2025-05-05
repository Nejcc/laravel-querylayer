<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Demo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Nejcc\LaravelQuerylayer\Demo\Models\User;
use Nejcc\LaravelQuerylayer\Repositories\BaseRepository;

final class UserRepository extends BaseRepository
{
    /**
     * Get active users with pagination.
     */
    public function getActiveUsers(?int $perPage = null): LengthAwarePaginator
    {
        return $this->query()
            ->active()
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get users by role with their posts.
     */
    public function getUsersByRoleWithPosts(string $role): Collection
    {
        return $this->with('posts')
            ->query()
            ->role($role)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get admins with both posts and comments, paginated.
     */
    public function getAdminsWithRelations(?int $perPage = null): LengthAwarePaginator
    {
        return $this->with(['posts', 'comments'])
            ->query()
            ->role('admin')
            ->active()
            ->paginate($perPage);
    }

    /**
     * Get users with active posts count.
     */
    public function getUsersWithPostCount(): Collection
    {
        return $this->query()
            ->withCount(['posts' => function (Builder $query) {
                $query->where('is_published', true);
            }])
            ->having('posts_count', '>', 0)
            ->orderBy('posts_count', 'desc')
            ->get();
    }

    /**
     * Create a new user and their initial post in a transaction.
     */
    public function createUserWithPost(array $userData, array $postData): User
    {
        return $this->transaction(function () use ($userData, $postData) {
            $user = $this->create($userData);

            // Create post using the post repository
            $postRepository = app(PostRepository::class);
            $postData['user_id'] = $user->id;
            $postRepository->create($postData);

            return $user->load('posts');
        });
    }

    /**
     * Restore a deleted user and their related posts.
     */
    public function restoreWithPosts(int $userId): bool
    {
        if (! $this->restore($userId)) {
            return false;
        }

        // Find related posts and restore them too
        $postRepository = app(PostRepository::class);
        $deletedPosts = $postRepository->withTrashed()
            ->query()
            ->where('user_id', $userId)
            ->whereNotNull('deleted_at')
            ->get();

        foreach ($deletedPosts as $post) {
            $postRepository->restore($post->id);
        }

        return true;
    }

    /**
     * Get users with raw database queries using the db() method.
     */
    public function getUsersUsingDbQuery(string $role): Collection
    {
        return $this->db()
            ->where('role', $role)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Count users by role using direct database access.
     */
    public function countUsersByRole(): Collection
    {
        return $this->db()
            ->select('role', $this->db()->raw('COUNT(*) as count'))
            ->groupBy('role')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Find users who haven't created any posts using a join.
     */
    public function findUsersWithoutPosts(): Collection
    {
        return $this->db()
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->whereNull('posts.id')
            ->select('users.*')
            ->get();
    }

    /**
     * Search users with dynamic conditions using db().
     */
    public function searchUsers(array $filters): Collection
    {
        $query = $this->db();

        if (isset($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Define which model the repository works with.
     */
    protected function model(): string
    {
        return User::class;
    }
}
