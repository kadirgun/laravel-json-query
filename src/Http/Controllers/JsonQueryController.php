<?php

namespace KadirGun\JsonQuery\Http\Controllers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use KadirGun\JsonQuery\JsonQuery;
use KadirGun\JsonQuery\JsonQueryData;

class JsonQueryController
{
    public function __invoke(JsonQueryData $request, string $model)
    {
        /** @var Model|null $model */
        $model = config("json-query.route.models.{$model}", null);

        if (!$model) {
            abort(404, "Model {$model} not found.");
        }

        if (Gate::has('json-query')) {
            Gate::authorize('json-query', $model);
        }

        $builder = app(JsonQuery::class, [
            'subject' => $model::query(),
            'request' => $request,
        ]);

        $result = $builder->build();

        if ($result instanceof Builder) {
            $result = $result->get();
        }

        return response()->json($result);
    }
}
