<?php

// config for KadirGun/JsonQuery
return [
    'route' => [
        'enabled' => true,
        'path' => 'json-query',
        'middleware' => ['api'],
        'models' => [
            // 'users' => \App\Models\User::class,
        ]
    ],
    'limits' => [
        // Maximum number of method calls allowed in a single query
        'method_count' => 20,
        // Maximum depth of nested queries
        'max_depth' => 10,
    ],
    'allowed_methods' => [
        'first',
        'get',
        'paginate',
        'where',
        'orWhere',
        'whereIn',
        'whereNotIn',
        'whereNull',
        'whereNotNull',
        'orderBy',
        'limit',
        'offset',
        'select',
        'with',
        // Add more methods as needed
    ],
    'allow_all_methods' => false,
];
