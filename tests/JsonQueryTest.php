<?php

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use KadirGun\JsonQuery\Exceptions\MethodNotAllowedException;
use KadirGun\JsonQuery\JsonQuery;
use Workbench\App\Models\User;

test('build where clause', function () {
    $request = new Request([
        'methods' => [
            [
                'name' => 'where',
                'parameters' => ['id', '=', 1],
            ],
        ],
    ]);

    $query = JsonQuery::for(User::class, $request)->build();
    $expectedQuery = User::query()->where('id', '=', 1);

    expect($query->toSql())->toBe($expectedQuery->toSql());
});

test('build where clause with multiple parameters', function () {
    $request = new Request([
        'methods' => [
            [
                'name' => 'where',
                'parameters' => ['id', '=', 1],
            ],
            [
                'name' => 'where',
                'parameters' => ['email', '=', 'test@test.com'],
            ],
        ],
    ]);

    $query = JsonQuery::for(User::class, $request)->build();
    $expectedQuery = User::query()->where('id', '=', 1)
        ->where('email', '=', 'test@test.com');
    expect($query->toSql())->toBe($expectedQuery->toSql());
});

test('build with empty methods', function () {
    $request = new Request([
        'methods' => [],
    ]);

    $query = JsonQuery::for(User::class, $request)->build();
    $expectedQuery = User::query();

    expect($query->toSql())->toBe($expectedQuery->toSql());
});

test('build with relation', function () {
    $requestFile = file_get_contents(__DIR__.'/fixtures/with_relation.json');
    $requestData = json_decode($requestFile, true);
    $request = new Request($requestData);

    User::factory()->create(['id' => 1]);

    DB::enableQueryLog();
    JsonQuery::for(User::query()->with('posts'), $request)->build()->get();
    $queryLogs = DB::getQueryLog();
    DB::flushQueryLog();

    DB::enableQueryLog();
    User::query()
        ->where('id', '=', 1)
        ->with(
            ['posts' => function ($query) {
                $query->where('status', '=', 'published');
            }]
        )->get();
    $expectedQueryLogs = DB::getQueryLog();
    DB::flushQueryLog();

    foreach ($queryLogs as $index => $log) {
        expect($log['query'])->toBe($expectedQueryLogs[$index]['query']);
    }
});

test('build with nested methods', function () {
    $requestFile = file_get_contents(__DIR__.'/fixtures/nested_methods.json');
    $requestData = json_decode($requestFile, true);
    $request = new Request($requestData);

    $user = User::factory()->create(['id' => 1]);
    $user->posts()->create([
        'title' => 'Test Post',
        'status' => 'published',
    ]);

    DB::enableQueryLog();
    JsonQuery::for(User::query(), $request)->build()->get();
    $queryLogs = DB::getQueryLog();
    DB::flushQueryLog();

    DB::enableQueryLog();
    User::query()
        ->where('id', '=', 1)
        ->with([
            'posts' => function (Builder $query) {
                $query->where('status', '=', 'published');
                $query->with([
                    'comments' => function (Builder $query) {
                        $query->where('status', '=', 'approved');
                    },
                ]);
            },
        ])->get();
    $expectedQueryLogs = DB::getQueryLog();
    DB::flushQueryLog();

    foreach ($queryLogs as $index => $log) {
        expect($log['query'])->toBe($expectedQueryLogs[$index]['query']);
    }
});

test('allow methods', function () {
    config(['json-query.allow_methods' => []]);

    $request = new Request([
        'methods' => [
            [
                'name' => 'where',
                'parameters' => ['id', '=', 1],
            ],
        ],
    ]);

    $builder = JsonQuery::for(User::class, $request);
    expect(fn () => $builder->build()->get())->toThrow(MethodNotAllowedException::class);

    $builder = JsonQuery::for(User::class, $request)->allowMethods(['where']);
    expect(fn () => $builder->build()->get())->not()->toThrow(MethodNotAllowedException::class);

    $builder = JsonQuery::for(User::class, $request)->allowMethods('where', 'find');
    expect(fn () => $builder->build()->get())->not()->toThrow(MethodNotAllowedException::class);

    $builder = JsonQuery::for(User::class, $request)->allowAllMethods();
    expect(fn () => $builder->build()->get())->not()->toThrow(MethodNotAllowedException::class);
});
