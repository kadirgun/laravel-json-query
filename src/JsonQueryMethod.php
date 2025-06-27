<?php

namespace KadirGun\JsonQuery;

use Illuminate\Support\Facades\Gate;

class JsonQueryMethod
{
    /**
     * The name of the method to be called.
     */
    public string $name;

    /**
     * The parameters to be passed to the method.
     */
    public array $parameters = [];

    /**
     * The subject on which the method will be called.
     */
    public mixed $subject;

    /**
     * Create a new JsonQueryMethod instance.
     */
    public function __construct(string $name, array $parameters, mixed $subject)
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->subject = $subject;
    }

    public function authorize(): self
    {
        $ability = config('json-query.authorization.gate_ability', 'json-query');

        if (Gate::has($ability)) {
            Gate::authorize($ability, [$this]);
        }

        $policy = Gate::getPolicyFor(JsonQueryMethod::class);

        if (! $policy) {
            return $this;
        }

        Gate::inspect($this->name, [$this])->authorize();

        return $this;
    }

    public function call(): mixed
    {
        return $this->subject->{$this->name}(...$this->parameters);
    }
}
