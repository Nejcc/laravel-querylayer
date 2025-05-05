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
interface RepositoryInterface
{
    /**
     * Get all items.
     *
     * @return Collection<int, TModel>
     */
    public function all(): Collection;

    /**
     * Get paginated results.
     *
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(?int $perPage = null): LengthAwarePaginator;

    /**
     * Find by ID.
     *
     * @return TModel|null
     */
    public function find(int|string $id): ?Model;

    /**
     * Find by column value.
     *
     * @return TModel|null
     */
    public function findBy(string $column, mixed $value): ?Model;

    /**
     * Get all matching conditions.
     *
     * @param  array<string, mixed>  $conditions
     * @return Collection<int, TModel>
     */
    public function where(array $conditions): Collection;

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(array $data): Model;

    /**
     * Update record by ID.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data): bool;

    /**
     * Delete record by ID.
     */
    public function delete(int|string $id): bool;

    /**
     * Expose query builder.
     *
     * @return Builder<TModel>
     */
    public function query(): Builder;

    /**
     * Expose raw DB query builder (Illuminate\DB).
     */
    public function db(): \Illuminate\Database\Query\Builder;

    /**
     * Include soft deleted records in the results.
     *
     * @return self<TModel>
     */
    public function withTrashed(): self;

    /**
     * Only include soft deleted records in the results.
     *
     * @return self<TModel>
     */
    public function onlyTrashed(): self;

    /**
     * Restore a soft deleted record.
     */
    public function restore(int|string $id): bool;

    /**
     * Permanently delete a soft deleted record.
     */
    public function forceDelete(int|string $id): bool;

    /**
     * Execute a callback within a transaction.
     *
     * @template TReturn
     * @param callable(): TReturn $callback
     * @return TReturn
     *
     * @throws \Throwable
     */
    public function transaction(callable $callback): mixed;

    /**
     * Create a new record within a transaction.
     *
     * @param  array<string, mixed>  $data
     * @return TModel
     * 
     * @throws \Throwable
     */
    public function createOrFail(array $data): Model;

    /**
     * Update record by ID within a transaction.
     *
     * @param  array<string, mixed>  $data
     * @throws \Throwable
     */
    public function updateOrFail(int|string $id, array $data): bool;

    /**
     * Eager load relations.
     *
     * @param string|array<string|callable> $relations
     * @return self<TModel>
     */
    public function with(string|array $relations): self;
}
