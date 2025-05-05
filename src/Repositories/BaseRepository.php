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
     * Trashed state for query builder.
     */
    protected string $trashedState = 'none';

    /**
     * Relations to eager load.
     *
     * @var array<string|callable>
     */
    protected array $with = [];

    /**
     * @throws BindingResolutionException
     */
    public function __construct()
    {
        $this->model = $this->resolveModel();
    }

    /**
     * Define which model the repository works with.
     *
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    final public function all(): Collection
    {
        $result = $this->applyTrashedState($this->applyEagerLoading($this->query()))->get();
        $this->reset();

        return $result;
    }

    final public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? (int) request()->input('per_page', config('database.pagination_default', 15));

        $result = $this->applyTrashedState($this->applyEagerLoading($this->query()))->paginate($perPage);
        $this->reset();

        return $result;
    }

    final public function find(int|string $id): ?Model
    {
        $result = $this->applyTrashedState($this->applyEagerLoading($this->query()))->find($id);
        $this->reset();

        return $result;
    }

    final public function findBy(string $column, mixed $value): ?Model
    {
        $result = $this->applyTrashedState($this->applyEagerLoading($this->query()))->where($column, $value)->first();
        $this->reset();

        return $result;
    }

    final public function where(array $conditions): Collection
    {
        $result = $this->applyTrashedState($this->applyEagerLoading($this->query()))->where($conditions)->get();
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
     * Reset query scopes and eager loading.
     *
     * @return $this<TModel>
     */
    final public function reset(): self
    {
        $this->with = [];
        $this->trashedState = 'none';

        return $this;
    }

    final public function query(): Builder
    {
        /** @var Builder<TModel> $query */
        $query = $this->model->newQuery();

        return $query;
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
