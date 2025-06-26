<?php

use Illuminate\Http\Request;
use KadirGun\JsonQuery\Exceptions\MethodCountExceededException;
use KadirGun\JsonQuery\Exceptions\MethodDepthExceededException;
use KadirGun\JsonQuery\JsonQuery;
use Workbench\App\Models\User;

test('method count limit', function () {
    $data = [
        'methods' => array_fill(0, 21, [
            'name' => 'where',
            'parameters' => ['id', 1],
        ]),
    ];

    $request = new Request($data);

    $builder = JsonQuery::for(User::query(), $request);

    config(['json-query.limits.method_count' => 20]);
    expect(fn () => $builder->build())->toThrow(MethodCountExceededException::class);

    config(['json-query.limits.method_count' => 21]);
    expect(fn () => $builder->build())->not->toThrow(MethodCountExceededException::class);
});

test('depth limit', function () {
    $data = [
        'methods' => [
            [
                'name' => 'where',
                'parameters' => [
                    [
                        'methods' => [
                            [
                                'name' => 'where',
                                'parameters' => [
                                    [
                                        'methods' => [
                                            [
                                                'name' => 'where',
                                                'parameters' => [
                                                    [
                                                        'methods' => [
                                                            [
                                                                'name' => 'where',
                                                                'parameters' => ['id', 1],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $request = new Request($data);

    config(['json-query.limits.max_depth' => 3]);
    $builder = JsonQuery::for(User::query(), $request);
    expect(fn () => $builder->build())->toThrow(MethodDepthExceededException::class);

    config(['json-query.limits.max_depth' => 4]);
    $builder = JsonQuery::for(User::query(), $request);
    expect(fn () => $builder->build())->not->toThrow(MethodDepthExceededException::class);
});
