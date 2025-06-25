<?php

namespace KadirGun\JsonQuery;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use KadirGun\JsonQuery\Commands\JsonQueryCommand;

class JsonQueryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-json-query')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_json_query_table')
            ->hasCommand(JsonQueryCommand::class);
    }
}
