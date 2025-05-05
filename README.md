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
$users = $userRepository->all();
$user = $userRepository->find(1);
$activeUsers = $userRepository->where(['is_active' => true]);

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
| `all()` | Get all records |
| `paginate(?int $perPage = null)` | Get paginated results |
| `find(int|string $id)` | Find a record by ID |
| `findBy(string $column, mixed $value)` | Find a record by column value |
| `where(array $conditions)` | Get all records matching conditions |
| `create(array $data)` | Create a new record |
| `update(int|string $id, array $data)` | Update a record |
| `delete(int|string $id)` | Delete a record |
| `query()` | Get the query builder instance |
| `db()` | Get the raw DB query builder instance |

## Singleton Pattern

The package implements the singleton pattern for repository instances. This means:

- Each model class gets a single repository instance
- Subsequent calls to `LaravelQuerylayer::repository()` with the same model return the same instance
- This helps reduce memory usage and improves performance
- Perfect for dependency injection and service container usage

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
