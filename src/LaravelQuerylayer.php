<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer;

use Nejcc\LaravelQuerylayer\Repositories\BaseRepository;

final class LaravelQuerylayer
{
    /**
     * @var array<string, BaseRepository>
     */
    private static array $repositories = [];

    /**
     * Create a new repository instance for the given model.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $model
     * @return BaseRepository<TModel>
     */
    public static function repository(string $model): BaseRepository
    {
        if (!isset(self::$repositories[$model])) {
            self::$repositories[$model] = new class($model) extends BaseRepository
            {
                protected string $modelClass;

                public function __construct(string $model)
                {
                    $this->modelClass = $model;
                    parent::__construct();
                }

                protected function model(): string
                {
                    return $this->modelClass;
                }
            };
        }

        return self::$repositories[$model];
    }
}
