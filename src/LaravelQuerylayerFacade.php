<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Nejcc\LaravelQuerylayer\Repositories\BaseRepository repository(string $model)
 */
final class LaravelQuerylayerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-querylayer';
    }
}
