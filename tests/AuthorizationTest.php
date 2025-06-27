<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use KadirGun\JsonQuery\JsonQuery;
use KadirGun\JsonQuery\JsonQueryMethod;
use Mockery\MockInterface;
use Workbench\App\Models\User;
use Workbench\App\Policies\JsonQueryPolicy;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\instance;

test('authorize query method', function () {
    config()->set('json-query.authorization.enabled', true);
    $user = User::factory()->create();

    actingAs($user);

    $query = [
        'methods' => [
            [
                'name' => 'where',
                'parameters' => [
                    'column' => 'name',
                    'operator' => '=',
                    'value' => 'John Doe',
                ],
            ],
        ],
    ];

    $request = new Request($query);

    $expectedMethod = new JsonQueryMethod('test', [], null);

    Gate::define('json-query', function ($user, $method) use (&$expectedMethod) {
        $expectedMethod = $method;

        return false;
    });

    $query = JsonQuery::for(User::query(), $request);
    expect(fn () => $query->build()->get())->toThrow(AuthorizationException::class);

    expect($expectedMethod)->not()->toBeNull();
    expect($expectedMethod)->toBeInstanceOf(JsonQueryMethod::class);
    expect($expectedMethod->name)->toBe('where');
    expect($expectedMethod->subject)->toBeInstanceOf(Builder::class);
    expect($expectedMethod->subject->getModel())->toBeInstanceOf(User::class);
});

test('authorize query with policy', function () {
    config()->set('json-query.authorization.enabled', true);
    $user = User::factory()->create();

    actingAs($user);

    $query = [
        'methods' => [
            [
                'name' => 'where',
                'parameters' => [
                    'column' => 'name',
                    'operator' => '=',
                    'value' => 'John Doe',
                ],
            ],
        ],
    ];

    $request = new Request($query);

    $mockedPolicy = mock(JsonQueryPolicy::class, function (MockInterface $mock) {
        $mock->shouldReceive('where')
            ->once()
            ->andReturn(false);
    });

    instance(JsonQueryPolicy::class, $mockedPolicy);

    Gate::policy(JsonQueryMethod::class, JsonQueryPolicy::class);

    $query = JsonQuery::for(User::query(), $request);
    expect(fn () => $query->build()->get())->toThrow(AuthorizationException::class);

    app()->forgetInstance(JsonQueryPolicy::class);

    $query = JsonQuery::for(User::query(), $request);
    expect(fn () => $query->build()->get())->toThrow(AuthorizationException::class);
});
