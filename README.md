# Laravel QueryLayer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nejcc/laravel-querylayer.svg?style=flat-square)](https://packagist.org/packages/nejcc/laravel-querylayer)
[![Total Downloads](https://img.shields.io/packagist/dt/nejcc/laravel-querylayer.svg?style=flat-square)](https://packagist.org/packages/nejcc/laravel-querylayer)
[![GitHub Actions](https://github.com/nejcc/laravel-querylayer/actions/workflows/main.yml/badge.svg)](https://github.com/nejcc/laravel-querylayer/actions)
[![License](https://img.shields.io/packagist/l/nejcc/laravel-querylayer.svg?style=flat-square)](LICENSE.md)

A powerful and flexible repository pattern implementation for Laravel applications. This package provides a clean and maintainable way to handle database operations in your Laravel projects, with built-in singleton pattern support for efficient repository management.

## Features

- 🚀 Quick repository creation for any Eloquent model
- 🔄 Full CRUD operations out of the box
- 📦 Type-safe with PHP 8.0+ features and generics support
- 🎯 Query builder access for complex queries
- 📱 Pagination support
- 🛠️ Extensible base repository for custom implementations
- 💡 IDE-friendly with comprehensive PHPDoc annotations
- 🔒 Singleton pattern implementation for efficient resource usage
- 🗑️ Soft delete support for models using SoftDeletes
- 🔄 Database transaction support for reliable operations
- 🔗 Eager loading support for optimized relationship queries
- 🧹 Automatic query scope reset for cleaner code

## Requirements

- PHP 8.0 or higher
- Laravel 8.0 or higher

## Installation

You can install the package via composer:

```bash
composer require nejcc/laravel-querylayer
```

The package will automatically register its service provider.

## Quick Start

### Using the Facade

The simplest way to create a repository for any model:

```php
use Nejcc\LaravelQuerylayer\Facades\LaravelQuerylayer;
use App\Models\User;

// Get a repository instance for the User model
// The same instance will be returned for subsequent calls with the same model
$userRepository = LaravelQuerylayer::repository(User::class);

// Basic operations
$users = $userRepository->where(['is_active' => true])->paginate(15);
$user = $userRepository->find(1);
$activeUsers = $userRepository->where(['is_active' => true])->get();

// Pagination
$paginatedUsers = $userRepository->paginate();

// Create and update
$newUser = $userRepository->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

$userRepository->update(1, ['name' => 'Jane Doe']);

// Delete
$userRepository->delete(1);

// Eager loading relationships
$userWithPosts = $userRepository->with('posts')->find(1);

// Soft deletes - if your model uses SoftDeletes
$trashedUser = $userRepository->withTrashed()->find(5);
$onlyTrashedUsers = $userRepository->onlyTrashed()->paginate(10);
$userRepository->restore(1);
$userRepository->forceDelete(1);

// Transactions
$userRepository->transaction(function () use ($userRepository) {
    // Execute multiple operations in a transaction
    $user = $userRepository->create(['name' => 'Transaction User']);
    $userRepository->update($user->id, ['name' => 'Updated User']);
    return $user;
});

// Or use the convenient methods
$user = $userRepository->createOrFail(['name' => 'Safe Create']);
$success = $userRepository->updateOrFail(1, ['name' => 'Safe Update']);
```

### Creating Custom Repositories

For more complex scenarios, extend the `BaseRepository`:

```php
use Nejcc\LaravelQuerylayer\Repositories\BaseRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository
{
    protected function model(): string
    {
        return User::class;
    }

    /**
     * Get active admin users with pagination
     */
    public function getActiveAdminsPaginated(?int $perPage = null): LengthAwarePaginator
    {
        return $this->getActiveAdminsQuery()->paginate($perPage);
    }

    /**
     * Get query builder for active admin users
     */
    public function getActiveAdminsQuery(): Builder
    {
        return $this->query()
            ->where('is_active', true)
            ->where('role', 'admin');
    }
}
```

## Available Methods

The repository provides a comprehensive set of methods for database operations:

| Method | Description |
|--------|-------------|
| `all()` | Get all records (use with caution for large datasets) |
| `get()` | Get records for the current query |
| `paginate(?int $perPage = null)` | Get paginated results |
| `find(int|string $id)` | Find a record by ID |
| `findBy(string $column, mixed $value)` | Find a record by column value |
| `where(array $conditions)` | Add where conditions to the query |
| `create(array $data)` | Create a new record |
| `update(int|string $id, array $data)` | Update a record |
| `delete(int|string $id)` | Delete a record |
| `query()` | Get the query builder instance |
| `db()` | Get the raw DB query builder instance |
| `with(string\|array $relations)` | Eager load relationships |
| `withTrashed()` | Include soft deleted records |
| `onlyTrashed()` | Get only soft deleted records |
| `restore(int\|string $id)` | Restore a soft deleted record |
| `forceDelete(int\|string $id)` | Permanently delete a record |
| `transaction(callable $callback)` | Execute operations in a transaction |
| `createOrFail(array $data)` | Create a record in a transaction |
| `updateOrFail(int\|string $id, array $data)` | Update a record in a transaction |
| `reset()` | Reset query scopes and eager loading |

## Method Chaining

The repository supports fluent method chaining for building complex queries:

```php
// Chain methods for a single query
$adminUsers = $userRepository
    ->with('posts')
    ->where(['role' => 'admin'])
    ->paginate(15);  // Pagination instead of all() for large datasets

// For limited dataset, you can use get()
$recentPosts = $postRepository
    ->query()
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

// Query scopes are automatically reset after execution
// So this query will NOT include any trashed records
$activeUsers = $userRepository->where(['is_active' => true])->get();

// You can also manually reset query scopes
$userRepository
    ->withTrashed()
    ->with('posts')
    ->reset() // Reset all query scopes
    ->where(['is_admin' => true])
    ->get(); // Will NOT include trashed records or eager load posts
```

## Singleton Pattern

The package implements the singleton pattern for repository instances. This means:

- Each model class gets a single repository instance
- Subsequent calls to `LaravelQuerylayer::repository()` with the same model return the same instance
- This helps reduce memory usage and improves performance
- Perfect for dependency injection and service container usage

## Soft Deletes

If your model uses Laravel's `SoftDeletes` trait, the repository automatically supports:

- Regular queries will exclude soft deleted records
- Use `withTrashed()` to include soft deleted records
- Use `onlyTrashed()` to get only soft deleted records
- Restore soft deleted records with `restore(id)`
- Permanently delete with `forceDelete(id)`

## Transactions

For data integrity, the repository provides transaction support:

- Wrap multiple operations in `transaction(function() { ... })`
- Use `createOrFail()` to ensure a record is created or transaction fails
- Use `updateOrFail()` to ensure an update succeeds or transaction fails

## Eager Loading

Optimize database queries by eager loading relationships:

- Use `with('relation')` to load a single relation
- Use `with(['relation1', 'relation2'])` to load multiple relations
- Chain with other methods: `with('posts')->where(['active' => true])->get()`

## Direct Database Access with db()

In addition to the Eloquent query builder accessible via `query()`, the repository provides raw database access through the `db()` method:

```php
// Get raw database query builder for direct table operations
$usersTable = $userRepository->db();

// Perform complex SQL operations
$activeUsersByRole = $userRepository->db()
    ->select('role', $userRepository->db()->raw('COUNT(*) as count'))
    ->where('is_active', true)
    ->groupBy('role')
    ->orderBy('count', 'desc')
    ->get();

// Complex joins and aggregates
$userStats = $userRepository->db()
    ->select(
        'users.id', 
        'users.name',
        $userRepository->db()->raw('COUNT(posts.id) as total_posts'),
        $userRepository->db()->raw('SUM(CASE WHEN posts.is_published = 1 THEN 1 ELSE 0 END) as published_posts')
    )
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->groupBy('users.id', 'users.name')
    ->having('total_posts', '>', 0)
    ->orderBy('total_posts', 'desc')
    ->get();

// Useful for complex search functionality
$query = $userRepository->db();
if ($request->has('search')) {
    $query->where('name', 'like', "%{$request->search}%");
}
if ($request->has('role')) {
    $query->where('role', $request->role);
}
$results = $query->orderBy('name')->get();

// Direct database updates or bulk operations
$affected = $userRepository->db()
    ->whereIn('id', $userIds)
    ->update(['is_active' => false]);
```

The `db()` method provides:

- Direct access to Laravel's Query Builder for raw SQL operations
- Perfect for complex joins, aggregations, and statistical queries
- Useful for bulk operations that would be inefficient with Eloquent
- Access to advanced SQL features not available in Eloquent

When to use `db()` vs `query()`:

- Use `query()` when you need model features like relationships, accessors/mutators, and model events
- Use `db()` when you need raw performance, complex joins, or SQL-specific functionality
- Choose `db()` for statistical queries and reporting where you don't need full model instances

You can find more examples of using the `db()` method in the demo repositories:
- UserRepository - examples of grouping and counting
- PostRepository - examples of joins and complex filtering
- CommentRepository - examples of aggregation and bulk operations

## Testing

The package includes a comprehensive test suite. Run the tests with:

```bash
composer test
```

## Code Style

This package follows the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard. Format your code using Laravel Pint:

```bash
# Format all files
composer format

# Check formatting without making changes
composer format-test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email info@after.si instead of using the issue tracker.

## Credits

- [Nejcc](https://github.com/nejcc)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Model Interface

You can also use the repository functionality directly on your models by implementing the `ModelInterface` and using the `HasRepository` trait:

```php
use Nejcc\LaravelQuerylayer\Contracts\ModelInterface;
use Nejcc\LaravelQuerylayer\Traits\HasRepository;

class User extends Model implements ModelInterface
{
    use HasRepository;

    protected static function getRepositoryClass(): string
    {
        return UserRepository::class;
    }
}
```

Now you can use repository methods directly on your model:

```php
// Create a new user
$user = User::createRecord([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Find a user
$user = User::findById(1);
$user = User::findByColumn('email', 'john@example.com');

// Get all users
$users = User::getAll();

// Get paginated users
$users = User::getPaginated(10);

// Get users with conditions
$users = User::getWhere(['role' => 'admin']);

// Update a user
User::updateRecord(1, ['name' => 'Jane Doe']);

// Delete a user
User::deleteRecord(1);

// Get the query builder
$query = User::getQuery();

// Get the repository instance
$repository = User::getRepository();
```

This approach gives you the flexibility to use repository methods either through the repository class or directly on your models.
