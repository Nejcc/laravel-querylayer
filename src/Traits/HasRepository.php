<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Nejcc\LaravelQuerylayer\Contracts\ModelInterface;
use Nejcc\LaravelQuerylayer\Contracts\RepositoryInterface;
use Nejcc\LaravelQuerylayer\Repositories\BaseRepository;

/**
 * @template TModel of Model
 *
 * @implements ModelInterface<TModel>
 */
trait HasRepository
{
    /**
     * Get the repository class for this model
     *
     * @return class-string<BaseRepository>
     */
    abstract protected static function getRepositoryClass(): string;

    /**
     * Get the repository instance for this model
     *
     * @return RepositoryInterface<TModel>
     */
    public static function getRepository(): RepositoryInterface
    {
        $repositoryClass = static::getRepositoryClass();

        return new $repositoryClass();
    }

    /**
     * Get all records
     *
     * @return Collection<int, TModel>
     */
    public static function getAll(): Collection
    {
        return static::getRepository()->all();
    }

    /**
     * Get paginated records
     */
    public static function getPaginated(?int $perPage = null): LengthAwarePaginator
    {
        return static::getRepository()->paginate($perPage);
    }

    /**
     * Find a record by ID
     *
     * @return TModel|null
     */
    public static function findById(int|string $id): ?Model
    {
        return static::getRepository()->find($id);
    }

    /**
     * Find a record by column value
     *
     * @return TModel|null
     */
    public static function findByColumn(string $column, mixed $value): ?Model
    {
        return static::getRepository()->findBy($column, $value);
    }

    /**
     * Create a new record
     *
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public static function createRecord(array $data): Model
    {
        return static::getRepository()->create($data);
    }

    /**
     * Update a record
     *
     * @param  array<string, mixed>  $data
     */
    public static function updateRecord(int|string $id, array $data): bool
    {
        return static::getRepository()->update($id, $data);
    }

    /**
     * Delete a record
     */
    public static function deleteRecord(int|string $id): bool
    {
        return static::getRepository()->delete($id);
    }

    /**
     * Get records matching conditions
     *
     * @param  array<string, mixed>  $conditions
     * @return Collection<int, TModel>
     */
    public static function getWhere(array $conditions): Collection
    {
        return static::getRepository()->where($conditions);
    }

    /**
     * Get the query builder
     *
     * @return Builder<TModel>
     */
    public static function getQuery(): Builder
    {
        return static::getRepository()->getQuery();
    }
}
