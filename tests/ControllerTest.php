<?php

use Illuminate\Support\Facades\Gate;
use KadirGun\JsonQuery\JsonQueryData;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

test('model is registered', function () {
    config(['json-query.route.models.users' => User::class]);

    $user = User::factory()->create();

    $response = postJson(
        route('json-query.query', [
            'model' => 'users',
        ]),
        [
            'methods' => [
                [
                    'name' => 'where',
                    'parameters' => ['id', $user->id],
                ],
                [
                    'name' => 'first',
                ],
            ],
        ]
    );

    $response->assertOk();

    $response->assertJson([
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
        ],
    ]);
});

test('model is not registered', function () {
    $response = postJson(
        route('json-query.query', [
            'model' => 'xxxxxx',
        ]),
        []
    );

    $response->assertNotFound();
});

test('authorize json-query', function () {
    config(['json-query.route.models.users' => User::class]);

    $user = User::factory()->create();

    Gate::define('json-query', function ($user, $model, $request) {
        expect($model)->toBe(User::class);
        expect($request)->toBeInstanceOf(JsonQueryData::class);

        return true;
    });

    $response = actingAs($user)
        ->postJson(
            route('json-query.query', [
                'model' => 'users',
            ]),
            [
                'methods' => [
                    [
                        'name' => 'where',
                        'parameters' => ['id', $user->id],
                    ],
                    [
                        'name' => 'first',
                    ],
                ],
            ]
        );

    $response->assertOk();
});
