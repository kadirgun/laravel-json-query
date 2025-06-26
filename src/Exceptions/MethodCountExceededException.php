<?php

namespace KadirGun\JsonQuery\Exceptions;

class MethodCountExceededException extends \Exception
{
    /**
     * Create a new exception instance.
     *
     * @param int $count
     * @return void
     */
    public function __construct(int $count)
    {
        $limit = config('json-query.limits.method_count', 20);
        parent::__construct(
            "Method count limit exceeded. Maximum allowed is {$limit}, but received {$count} methods."
        );
    }
}
