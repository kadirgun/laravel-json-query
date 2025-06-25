<?php

namespace KadirGun\JsonQuery\Commands;

use Illuminate\Console\Command;

class JsonQueryCommand extends Command
{
    public $signature = 'laravel-json-query';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
