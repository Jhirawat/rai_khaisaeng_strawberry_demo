<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    private const HOSTILE_DATABASE_URL = 'mysql://root:@127.0.0.1:1/maeyangha_shop_codex';

    public function test_phpunit_neutralizes_an_inherited_database_url(): void
    {
        $process = $this->runSmokeTest([
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'maeyangha_shop_codex',
            'DB_URL' => self::HOSTILE_DATABASE_URL,
        ]);

        $output = $this->processOutput($process);

        $this->assertSame(0, $process->getExitCode(), $output);
        $this->assertStringNotContainsString('information_schema', $output);
        $this->assertStringNotContainsString('SQLSTATE', $output);
    }

    #[DataProvider('unsafeCachedDatabaseConfigurations')]
    public function test_cached_unsafe_database_configuration_fails_closed_before_migrations(
        array $connectionOverrides,
    ): void {
        $databasePath = null;

        if ($connectionOverrides['database'] === '__TEMP_SQLITE_DATABASE__') {
            $databasePath = tempnam(sys_get_temp_dir(), 'unsafe-laravel-database-');
            $this->assertNotFalse($databasePath);
            $connectionOverrides['database'] = $databasePath;
        }

        $cachePath = tempnam(storage_path('framework/testing'), 'unsafe-laravel-config-');
        $this->assertNotFalse($cachePath);
        $relativeCachePath = 'storage/framework/testing/'.basename($cachePath);

        try {
            $config = $this->app['config']->all();
            $config['database']['default'] = 'sqlite';
            $config['database']['connections']['sqlite'] = array_replace(
                $config['database']['connections']['sqlite'],
                $connectionOverrides,
            );

            $written = file_put_contents($cachePath, '<?php return '.var_export($config, true).';');
            $this->assertNotFalse($written);

            $process = $this->runSmokeTest([
                'APP_CONFIG_CACHE' => $relativeCachePath,
                'DB_URL' => self::HOSTILE_DATABASE_URL,
            ]);
            $output = $this->processOutput($process);

            $this->assertNotSame(0, $process->getExitCode(), $output);
            $this->assertStringContainsString('Unsafe test database configuration', $output);
            $this->assertStringNotContainsString('information_schema', $output);
            $this->assertStringNotContainsString('SQLSTATE', $output);
        } finally {
            @unlink($cachePath);

            if ($databasePath !== null) {
                @unlink($databasePath);
            }
        }
    }

    public static function unsafeCachedDatabaseConfigurations(): array
    {
        return [
            'URL resolves the named SQLite connection to MySQL' => [[
                'driver' => 'sqlite',
                'url' => self::HOSTILE_DATABASE_URL,
                'database' => ':memory:',
            ]],
            'SQLite database is file-backed' => [[
                'driver' => 'sqlite',
                'url' => null,
                'database' => '__TEMP_SQLITE_DATABASE__',
            ]],
        ];
    }

    private function runSmokeTest(array $environment): Process
    {
        $process = new Process(
            [PHP_BINARY, 'artisan', 'test', 'tests/Feature/SmokeTest.php', '--no-ansi', '--do-not-cache-result'],
            base_path(),
            $environment,
        );
        $process->setTimeout(45);
        $process->run();

        return $process;
    }

    private function processOutput(Process $process): string
    {
        return $process->getOutput().$process->getErrorOutput();
    }
}
