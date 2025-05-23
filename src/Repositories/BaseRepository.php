<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Repositories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nejcc\LaravelQuerylayer\Contracts\RepositoryInterface;
use RuntimeException;
use Throwable;

/**
 * @template TModel of Model
 *
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected readonly Model $model;

    /**
     * Cache TTL in seconds
     */
    protected int $cacheTtl = 3600;

    /**
     * Whether to use cache for queries
     */
    protected bool $useCache = false;

    /**
     * Cache key prefix for this repository
     */
    protected string $cachePrefix = 'repository_';

    /**
     * Trashed state for query builder.
     */
    protected string $trashedState = 'none';

    /**
     * Relations to eager load.
     *
     * @var array<string|callable>
     */
    protected array $with = [];

    protected Builder $currentQuery;

    /**
     * @throws BindingResolutionException
     */
    public function __construct()
    {
        $this->model = $this->resolveModel();
        $this->currentQuery = $this->query();
    }

    /**
     * Define which model the repository works with.
     *
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    final public function all(): Collection
    {
        $result = $this->applyTrashedState($this->applyEagerLoading($this->currentQuery))->get();
        $this->reset();

        return $result;
    }

    final public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? (int) request()->input('per_page', config('database.pagination_default', 15));

        $result = $this->applyTrashedState($this->applyEagerLoading($this->currentQuery))->paginate($perPage);
        $this->reset();

        return $result;
    }

    final public function find(int|string $id): ?Model
    {
        if ($this->useCache) {
            $cacheKey = $this->generateCacheKey('find', ['id' => $id]);

            return cache()->remember($cacheKey, $this->cacheTtl, function () use ($id) {
                $result = $this->applyTrashedState($this->applyEagerLoading($this->currentQuery))->find($id);
                $this->reset();

                return $result;
            });
        }

        $result = $this->applyTrashedState($this->applyEagerLoading($this->currentQuery))->find($id);
        $this->reset();

        return $result;
    }

    final public function findBy(string $column, mixed $value): ?Model
    {
        if ($this->useCache) {
            $cacheKey = $this->generateCacheKey('findBy', ['column' => $column, 'value' => $value]);

            return cache()->remember($cacheKey, $this->cacheTtl, function () use ($column, $value) {
                $result = $this->applyTrashedState($this->applyEagerLoading($this->currentQuery))->where($column, $value)->first();
                $this->reset();

                return $result;
            });
        }

        $result = $this->applyTrashedState($this->applyEagerLoading($this->currentQuery))->where($column, $value)->first();
        $this->reset();

        return $result;
    }

    /**
     * Add where conditions to the query for chaining
     *
     * @param  array<string, mixed>  $conditions
     * @return $this<TModel>
     */
    final public function whereCondition(array $conditions): self
    {
        $this->currentQuery->where($conditions);

        return $this;
    }

    /**
     * Get all matching conditions.
     *
     * @param  array<string, mixed>  $conditions
     * @return Collection<int, TModel>
     */
    final public function where(array $conditions): Collection
    {
        if ($this->useCache) {
            $cacheKey = $this->generateCacheKey('where', ['conditions' => $conditions]);

            return cache()->remember($cacheKey, $this->cacheTtl, function () use ($conditions) {
                $result = $this->applyTrashedState($this->applyEagerLoading($this->currentQuery))->where($conditions)->get();
                $this->reset();

                return $result;
            });
        }

        $result = $this->applyTrashedState($this->applyEagerLoading($this->currentQuery))->where($conditions)->get();
        $this->reset();

        return $result;
    }

    final public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    final public function update(int|string $id, array $data): bool
    {
        $record = $this->find($id);

        return $record ? $record->update($data) : false;
    }

    final public function delete(int|string $id): bool
    {
        $record = $this->find($id);

        return $record ? $record->delete() : false;
    }

    /**
     * Execute a callback within a transaction.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    final public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Create a new record within a transaction.
     *
     * @param  array<string, mixed>  $data
     * @return TModel
     *
     * @throws Throwable
     */
    final public function createOrFail(array $data): Model
    {
        return $this->transaction(function () use ($data) {
            return $this->create($data);
        });
    }

    /**
     * Update record by ID within a transaction.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    final public function updateOrFail(int|string $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->update($id, $data);

            if (! $result) {
                throw new RuntimeException("Failed to update record with ID {$id}");
            }

            return $result;
        });
    }

    /**
     * Eager load relations.
     *
     * @param  string|array<string|callable>  $relations
     * @return $this<TModel>
     */
    final public function with(string|array $relations): self
    {
        $this->with = is_string($relations) ? [$relations] : $relations;

        return $this;
    }

    /**
     * Enable caching for the next query
     *
     * @param  int|null  $ttl  Cache TTL in seconds
     * @return $this<TModel>
     */
    final public function cache(?int $ttl = null): self
    {
        $this->useCache = true;
        if ($ttl !== null) {
            $this->cacheTtl = $ttl;
        }

        return $this;
    }

    /**
     * Disable caching for the next query
     *
     * @return $this<TModel>
     */
    final public function withoutCache(): self
    {
        $this->useCache = false;

        return $this;
    }

    /**
     * Reset query scopes and eager loading.
     *
     * @return $this<TModel>
     */
    final public function reset(): self
    {
        $this->with = [];
        $this->trashedState = 'none';
        $this->useCache = false;
        $this->currentQuery = $this->model->newQuery();

        return $this;
    }

    final public function query(): Builder
    {
        if (! isset($this->currentQuery)) {
            $this->currentQuery = $this->model->newQuery();
        }

        return $this->currentQuery;
    }

    /**
     * Get a raw DB query builder for the model's table.
     */
    final public function db(): QueryBuilder
    {
        return DB::table($this->model->getTable());
    }

    /**
     * Include soft deleted records in the results.
     *
     * @return $this<TModel>
     */
    final public function withTrashed(): self
    {
        $this->trashedState = 'with';

        return $this;
    }

    /**
     * Only include soft deleted records in the results.
     *
     * @return $this<TModel>
     */
    final public function onlyTrashed(): self
    {
        $this->trashedState = 'only';

        return $this;
    }

    /**
     * Restore a soft deleted record.
     */
    final public function restore(int|string $id): bool
    {
        if (! $this->usesSoftDeletes()) {
            return false;
        }

        $record = $this->withTrashed()->find($id);

        if (! $record) {
            return false;
        }

        return $record->restore();
    }

    /**
     * Permanently delete a soft deleted record.
     */
    final public function forceDelete(int|string $id): bool
    {
        if (! $this->usesSoftDeletes()) {
            return false;
        }

        $record = $this->withTrashed()->find($id);

        if (! $record) {
            return false;
        }

        return $record->forceDelete();
    }

    /**
     * Get the results for the current query
     *
     * @return Collection<int, TModel>
     */
    final public function get(): Collection
    {
        $result = $this->applyTrashedState($this->applyEagerLoading($this->currentQuery))->get();
        $this->reset();

        return $result;
    }

    /**
     * Add a where clause with OR condition
     *
     * @param  array<string, mixed>  $conditions
     * @return $this<TModel>
     */
    final public function orWhere(array $conditions): self
    {
        $this->currentQuery->orWhere(function ($q) use ($conditions) {
            foreach ($conditions as $column => $value) {
                $q->where($column, $value);
            }
        });

        return $this;
    }

    /**
     * Add a where clause with nested conditions
     *
     * @return $this<TModel>
     */
    final public function whereNested(callable $callback): self
    {
        $this->query()->where(function ($query) use ($callback) {
            $callback($query);
        });

        return $this;
    }

    /**
     * Add a where clause with raw SQL
     *
     * @return $this<TModel>
     */
    final public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->query()->whereRaw($sql, $bindings);

        return $this;
    }

    /**
     * Apply a query scope
     *
     * @param  mixed  ...$parameters
     * @return $this<TModel>
     */
    final public function scope(string $scope, ...$parameters): self
    {
        $this->currentQuery->{$scope}(...$parameters);

        return $this;
    }

    /**
     * Get the query builder with all applied conditions
     *
     * @return Builder<TModel>
     */
    final public function getQuery(): Builder
    {
        return $this->applyTrashedState($this->applyEagerLoading($this->query()));
    }

    /**
     * Insert multiple records at once
     *
     * @param  array<array<string, mixed>>  $records
     */
    final public function insert(array $records): bool
    {
        try {
            DB::beginTransaction();
            foreach ($records as $record) {
                $this->model->newInstance($record)->save();
            }
            DB::commit();

            return true;
        } catch (Throwable $e) {
            DB::rollBack();

            return false;
        }
    }

    /**
     * Insert multiple records and get their IDs
     *
     * @param  array<array<string, mixed>>  $records
     * @return array<int>
     */
    final public function insertGetIds(array $records): array
    {
        $ids = [];
        try {
            DB::beginTransaction();
            foreach ($records as $record) {
                $model = $this->model->newInstance($record);
                $model->save();
                $ids[] = $model->getKey();
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $ids;
    }

    /**
     * Update multiple records based on a condition
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $conditions
     * @return int Number of affected rows
     */
    final public function updateWhere(array $values, array $conditions): int
    {
        $query = $this->currentQuery->where($conditions);
        $affected = $query->update($values);
        $this->reset();

        return $affected;
    }

    /**
     * Delete multiple records based on a condition
     *
     * @param  array<string, mixed>  $conditions
     * @return int Number of affected rows
     */
    final public function deleteWhere(array $conditions): int
    {
        $query = $this->currentQuery->where($conditions);
        $affected = $query->delete();
        $this->reset();

        return $affected;
    }

    /**
     * Process records in chunks
     */
    final public function chunk(int $chunkSize, callable $callback): bool
    {
        $result = $this->currentQuery->chunk($chunkSize, $callback);
        $this->reset();

        return $result;
    }

    /**
     * Process records in chunks with cursor
     */
    final public function chunkById(int $chunkSize, callable $callback): bool
    {
        $result = $this->currentQuery->chunkById($chunkSize, $callback);
        $this->reset();

        return $result;
    }

    /**
     * Generate a cache key for the current query
     */
    protected function generateCacheKey(string $method, array $parameters = []): string
    {
        $key = $this->cachePrefix.$this->model->getTable().'_'.$method;
        if (! empty($parameters)) {
            $key .= '_'.md5(serialize($parameters));
        }

        return $key;
    }

    /**
     * Resolve model instance.
     *
     * @throws BindingResolutionException
     */
    protected function resolveModel(): Model
    {
        $model = app()->make($this->model());

        if (! $model instanceof Model) {
            throw new RuntimeException("Class {$this->model()} must be an instance of Model");
        }

        return $model;
    }

    /**
     * Check if the model uses soft deletes.
     */
    protected function usesSoftDeletes(): bool
    {
        return in_array(
            SoftDeletes::class,
            class_uses_recursive($this->model())
        );
    }

    /**
     * Apply the trashed state to the query.
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyTrashedState(Builder $query): Builder
    {
        if (! $this->usesSoftDeletes()) {
            return $query;
        }

        return match ($this->trashedState) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => $query
        };
    }

    /**
     * Apply eager loading of relations to the query.
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyEagerLoading(Builder $query): Builder
    {
        if (empty($this->with)) {
            return $query;
        }

        return $query->with($this->with);
    }
}
