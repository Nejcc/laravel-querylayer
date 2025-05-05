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
     */
    public function paginate(?int $perPage = null): LengthAwarePaginator;

    /**
     * Find by ID.
     *
     * @return TModel|null
     */
    public function find(int|string $id): mixed;

    /**
     * Find by column value.
     *
     * @return TModel|null
     */
    public function findBy(string $column, mixed $value): mixed;

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
    public function create(array $data): mixed;

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
}
