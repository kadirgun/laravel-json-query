<?php

use Illuminate\Http\Request;
use Workbench\App\Models\User;
use KadirGun\JsonQuery\JsonQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

test('build where clause', function () {
    $request = new Request([
        'methods' => [
            [
                'name' => '@where',
                'parameters' => ['id', '=', 1]
            ]
        ]
    ]);

    $query = JsonQuery::for(User::class, $request)->build();
    $expectedQuery = User::query()->where('id', '=', 1);

    expect($query->toSql())->toBe($expectedQuery->toSql());
});

test('build where clause with multiple parameters', function () {
    $request = new Request([
        'methods' => [
            [
                'name' => '@where',
                'parameters' => ['id', '=', 1]
            ],
            [
                'name' => '@where',
                'parameters' => ['email', '=', 'test@test.com']
            ]
        ]
    ]);

    $query = JsonQuery::for(User::class, $request)->build();
    $expectedQuery = User::query()->where('id', '=', 1)
        ->where('email', '=', 'test@test.com');
    expect($query->toSql())->toBe($expectedQuery->toSql());
});

test('build with non-existing method', function () {
    $request = new Request([
        'methods' => [
            [
                'name' => 'nonExistingMethod',
                'parameters' => []
            ]
        ]
    ]);

    $builder = JsonQuery::for(User::class, $request);

    expect(fn() => $builder->build())->toThrow(\BadMethodCallException::class, 'Method nonExistingMethod does not exist on the subject.');
});

test('build with empty methods', function () {
    $request = new Request([
        'methods' => []
    ]);

    $query = JsonQuery::for(User::class, $request)->build();
    $expectedQuery = User::query();

    expect($query->toSql())->toBe($expectedQuery->toSql());
});

test('build with relation', function () {
    $requestFile = file_get_contents(__DIR__ . '/fixtures/with_relation.json');
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
    $requestFile = file_get_contents(__DIR__ . '/fixtures/nested_methods.json');
    $requestData = json_decode($requestFile, true);
    $request = new Request($requestData);

    $user = User::factory()->create(['id' => 1]);
    $user->posts()->create([
        'title' => 'Test Post',
        'status' => 'published'
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
                    }
                ]);
            }
        ])->get();
    $expectedQueryLogs = DB::getQueryLog();
    DB::flushQueryLog();

    dd($queryLogs, $expectedQueryLogs);

    foreach ($queryLogs as $index => $log) {
        expect($log['query'])->toBe($expectedQueryLogs[$index]['query']);
    }
});
