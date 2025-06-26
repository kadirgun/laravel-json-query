<?php

namespace KadirGun\JsonQuery\Exceptions;

class MethodNotAllowedException extends \Exception
{
    /**
     * Create a new exception instance.
     *
     * @return void
     */
    public function __construct(string $method)
    {
        parent::__construct("The method '{$method}' is not allowed.");
    }
}
