<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use KadirGun\JsonQuery\JsonQuery;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

test('authorize json-query-method', function () {
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

    Gate::define('json-query-method', function ($user, $method, $subject) {
        expect($method)->toBeArray();
        expect($method['name'])->toBe('where');
        expect($subject)->toBeInstanceOf(Builder::class);
        expect($subject->getModel())->toBeInstanceOf(User::class);

        return false;
    });

    $query = JsonQuery::for(User::query(), $request);
    expect(fn () => $query->build()->get())->toThrow(AuthorizationException::class);
});
