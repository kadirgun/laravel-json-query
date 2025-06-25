<?php

namespace KadirGun\JsonQuery;

use ArrayAccess;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @template TModel of Model
 * @mixin Builder<TModel>
 */
class JsonQuery implements ArrayAccess
{
    use ForwardsCalls;

    public function __construct(
        protected Builder|Relation $subject,
        protected ?Request $request = null
    ) {
        $this->request = $request
            ? JsonQueryData::fromRequest($request)
            : app(JsonQueryData::class);
    }

    public static function parseParameters(array $parameters): array
    {
        foreach ($parameters as $key => $value) {
            if (isset($value['methods']) && is_array($value['methods'])) {
                $parameters[$key] = function ($subject) use ($value) {
                    self::callMethods($value['methods'], $subject);

                    return $subject;
                };
            } else if (is_array($value)) {
                $parameters[$key] = self::parseParameters($value);
            }
        }

        return $parameters;
    }

    public static function callMethods(array $methods, mixed $subject)
    {
        foreach ($methods as $method) {
            self::callMethod($method, $subject);
        }
    }

    public static function callMethod(array $method, mixed $subject)
    {
        $name = str_replace('@', '', $method['name'] ?? '');
        $parameters = self::parseParameters($method['parameters'] ?? []);

        dump("Calling method: {$name} with parameters:", $parameters);

        $subject->{$name}(...$parameters);
    }

    public function build(): Builder|Relation
    {
        self::callMethods(
            $this->request->methods(),
            $this->subject
        );

        return $this->subject;
    }

    /**
     * @param Builder<TModel>|Relation|class-string<TModel> $subject
     * @return static<TModel>
     */
    public static function for(
        Builder|Relation|string $subject,
        ?Request $request = null
    ): static {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        /** @var static<TModel> $instance */
        $instance = new static($subject, $request);

        return $instance;
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
