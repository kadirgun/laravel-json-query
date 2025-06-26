<?php

namespace KadirGun\JsonQuery;

use ArrayAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\ForwardsCalls;
use KadirGun\JsonQuery\Exceptions\MethodCountExceededException;
use KadirGun\JsonQuery\Exceptions\MethodDepthExceededException;
use KadirGun\JsonQuery\Exceptions\MethodNotAllowedException;

/**
 * @template TModel of Model
 *
 * @mixin Builder<TModel>
 */
class JsonQuery implements ArrayAccess
{
    use ForwardsCalls;

    private int $depth = 1;

    public function __construct(
        protected Builder|Relation $subject,
        protected JsonQueryData|Request|null $request = null
    ) {
        if (! $this->request instanceof JsonQueryData) {
            $this->request = $request
                ? JsonQueryData::fromRequest($request)
                : app(JsonQueryData::class);
        }
    }

    /**
     * @param  Builder<TModel>|Relation|class-string<TModel>  $subject
     * @param  JsonQueryData|Request|null  $request
     * @return self<TModel>
     */
    public static function for(
        Builder|Relation|string $subject,
        JsonQueryData|Request|null $request = null
    ): self {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        /** @var self<TModel> $instance */
        $instance = new self($subject, $request);

        return $instance;
    }

    private function parseParameters(array $parameters): array
    {
        foreach ($parameters as $key => $value) {
            if (isset($value['methods']) && is_array($value['methods'])) {
                $this->depth++;

                if ($this->depth > config('json-query.limits.max_depth', 10)) {
                    throw new MethodDepthExceededException($this->depth);
                }

                $parameters[$key] = function ($subject) use ($value) {
                    $this->callMethods($value['methods'], $subject);

                    return $subject;
                };
            } elseif (is_array($value)) {
                $parameters[$key] = $this->parseParameters($value);
            }
        }

        return $parameters;
    }

    private function callMethods(array $methods, mixed $subject): mixed
    {
        $limit = config('json-query.limits.method_count', 20);

        if (count($methods) > $limit) {
            throw new MethodCountExceededException(count($methods));
        }

        $result = $subject;

        foreach ($methods as $method) {
            $result = $this->callMethod($method, $subject);
        }

        return $result;
    }

    private function callMethod(array $method, mixed $subject): mixed
    {
        $name = $method['name'];

        $allowedAllowedMethods = config('json-query.allow_all_methods', false);
        $allowedMethods = config('json-query.allowed_methods', []);

        if (! $allowedAllowedMethods && ! in_array($name, $allowedMethods)) {
            throw new MethodNotAllowedException("Method {$name} is not allowed.");
        }

        $parameters = $this->parseParameters($method['parameters'] ?? []);

        return $subject->{$name}(...$parameters);
    }

    public function build(): mixed
    {
        if (! $this->request instanceof JsonQueryData) {
            throw new \RuntimeException('Request must be an instance of JsonQueryData.');
        }

        $result = $this->callMethods(
            $this->request->methods(),
            $this->subject
        );

        return $result;
    }

    public function getSubject(): Builder|Relation
    {
        return $this->subject;
    }

    public function getEloquentBuilder(): Builder
    {
        if ($this->subject instanceof Builder) {
            return $this->subject;
        }

        return $this->subject->getQuery();
    }

    public function __call($name, $arguments)
    {
        $result = $this->forwardCallTo($this->subject, $name, $arguments);

        /*
         * If the forwarded method call is part of a chain we can return $this
         * instead of the actual $result to keep the chain going.
         */
        if ($result === $this->subject) {
            return $this;
        }

        return $result;
    }

    public function clone(): static
    {
        return clone $this;
    }

    public function __clone()
    {
        $this->subject = clone $this->subject;
    }

    public function __get($name)
    {
        return $this->subject->{$name};
    }

    public function __set($name, $value)
    {
        $this->subject->{$name} = $value;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->subject[$offset]);
    }

    public function offsetGet($offset): bool
    {
        return $this->subject[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->subject[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->subject[$offset]);
    }
}
