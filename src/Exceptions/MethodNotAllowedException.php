<?php

namespace KadirGun\JsonQuery\Exceptions;

class MethodNotAllowedException extends \Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $method
     * @return void
     */
    public function __construct(string $method)
    {
        parent::__construct("The method '{$method}' is not allowed.");
    }
}
