<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @template TModel of Model
 */
interface ModelInterface
{
    /**
     * Get all records
     *
     * @return Collection<int, TModel>
     */
    public static function getAll(): Collection;

    /**
     * Get paginated records
     */
    public static function getPaginated(?int $perPage = null): LengthAwarePaginator;

    /**
     * Find a record by ID
     *
     * @return TModel|null
     */
    public static function findById(int|string $id): ?Model;

    /**
     * Find a record by column value
     *
     * @return TModel|null
     */
    public static function findByColumn(string $column, mixed $value): ?Model;

    /**
     * Create a new record
     *
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public static function createRecord(array $data): Model;

    /**
     * Update a record
     *
     * @param  array<string, mixed>  $data
     */
    public static function updateRecord(int|string $id, array $data): bool;

    /**
     * Delete a record
     */
    public static function deleteRecord(int|string $id): bool;

    /**
     * Get records matching conditions
     *
     * @param  array<string, mixed>  $conditions
     * @return Collection<int, TModel>
     */
    public static function getWhere(array $conditions): Collection;

    /**
     * Get the query builder
     *
     * @return Builder<TModel>
     */
    public static function getQuery(): Builder;

    /**
     * Get the repository instance
     *
     * @return RepositoryInterface<TModel>
     */
    public static function getRepository(): RepositoryInterface;
}
