<?php

declare(strict_types=1);

/*
 * You can place your custom package configuration in here.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Default Pagination Per Page
    |--------------------------------------------------------------------------
    |
    | This value determines the default number of items per page when using
    | pagination in repositories.
    |
    */
    'pagination_default' => env('QUERYLAYER_PAGINATION_DEFAULT', 15),
];
