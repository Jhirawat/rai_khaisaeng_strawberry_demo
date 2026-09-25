<?php

namespace Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        $this->guardAgainstUnsafeTestDatabase($app);

        return $app;
    }

    private function guardAgainstUnsafeTestDatabase(Application $app): void
    {
        $connection = $app->make('db')->connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();

        if ($driver !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Unsafe test database configuration: resolved driver [%s] and database [%s]; expected [sqlite] and [:memory:].',
                $driver,
                $database,
            ));
        }
    }
}
