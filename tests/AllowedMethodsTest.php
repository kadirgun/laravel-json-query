<?php

use Illuminate\Http\Request;
use KadirGun\JsonQuery\Exceptions\MethodNotAllowedException;
use KadirGun\JsonQuery\JsonQuery;
use Workbench\App\Models\User;

test('allowed methods', function () {
    $user = User::factory()->create();

    $request = new Request([
        'methods' => [
            ['name' => 'where', 'parameters' => ['id', $user->id]],
            ['name' => 'first'],
        ],
    ]);

    config(['json-query.allow_all_methods' => false]);

    config(['json-query.allowed_methods' => ['where', 'first']]);
    $builder = JsonQuery::for(User::query(), $request);
    expect($builder->build())->toBeInstanceOf(User::class);

    config(['json-query.allowed_methods' => ['where']]);
    $builder = JsonQuery::for(User::query(), $request);
    expect(fn () => $builder->build())->toThrow(MethodNotAllowedException::class);

    config(['json-query.allow_all_methods' => true]);
    config(['json-query.allowed_methods' => []]);
    $builder = JsonQuery::for(User::query(), $request);
    expect($builder->build())->toBeInstanceOf(User::class);
});
