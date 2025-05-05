<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Repositories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nejcc\LaravelQuerylayer\Contracts\RepositoryInterface;
use RuntimeException;

/**
 * @template TModel of Model
 *
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected readonly Model $model;

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
        return $this->model->all();
    }

    final public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? (int) request()->input('per_page', config('database.pagination_default', 15));

        return $this->query()->paginate($perPage);
    }

    final public function find(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }

    final public function findBy(string $column, mixed $value): ?Model
    {
        return $this->query()->where($column, $value)->first();
    }

    final public function where(array $conditions): Collection
    {
        return $this->query()->where($conditions)->get();
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
}
