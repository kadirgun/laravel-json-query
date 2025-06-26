<?php

namespace KadirGun\JsonQuery\Exceptions;

class MethodDepthExceededException extends \Exception
{
    /**
     * Create a new exception instance.
     *
     * @return void
     */
    public function __construct(int $count)
    {
        $limit = config('json-query.limits.max_depth', 10);
        parent::__construct(
            "Method depth limit exceeded. Maximum allowed is {$limit}, but received {$count} levels of depth."
        );
    }
}
