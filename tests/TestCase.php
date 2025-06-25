<?php

namespace KadirGun\JsonQuery\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use function Orchestra\Testbench\workbench_path;
use KadirGun\JsonQuery\JsonQueryServiceProvider;

use Orchestra\Testbench\Attributes\WithMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;

#[WithMigration]
class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'KadirGun\\JsonQuery\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            JsonQueryServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(
            workbench_path('database/migrations')
        );
    }
}
