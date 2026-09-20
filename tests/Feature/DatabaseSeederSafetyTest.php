<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoProductionSeeder;
use Database\Seeders\ThaiAddressFullSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([ThaiAddressFullSeeder::class, DemoProductionSeeder::class] as $seeder) {
            $this->app->bind($seeder, fn () => new class extends Seeder
            {
                public function run(): void
                {
                    // DatabaseSeeder policy is the behavior under test.
                }
            });
        }
    }

    public function test_production_seeding_with_demo_data_disabled_does_not_create_known_credential_accounts(): void
    {
        $this->app->instance('env', 'production');
        config()->set('app.allow_demo_seed', false);

        $this->runDatabaseSeeder();

        foreach ($this->knownCredentialEmails() as $email) {
            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
    }

    public function test_local_seeding_keeps_known_credential_demo_accounts_available(): void
    {
        $this->app->instance('env', 'local');
        config()->set('app.allow_demo_seed', false);

        $this->runDatabaseSeeder();

        $demoUser = User::query()->where('email', 'user_test@khaisaeng.test')->firstOrFail();

        $this->assertTrue(Hash::check('password', $demoUser->password));
        $this->assertKnownCredentialAccountsExist();
    }

    public function test_production_seeding_can_explicitly_enable_known_credential_demo_accounts(): void
    {
        $this->app->instance('env', 'production');
        config()->set('app.allow_demo_seed', true);

        $this->runDatabaseSeeder();

        $demoUser = User::query()->where('email', 'user_test@khaisaeng.test')->firstOrFail();

        $this->assertTrue(Hash::check('password', $demoUser->password));
        $this->assertKnownCredentialAccountsExist();
    }

    private function runDatabaseSeeder(): void
    {
        $seeder = $this->app->make(DatabaseSeeder::class);
        $seeder->setContainer($this->app)->run();
    }

    private function assertKnownCredentialAccountsExist(): void
    {
        foreach ($this->knownCredentialEmails() as $email) {
            $this->assertDatabaseHas('users', ['email' => $email]);
        }
    }

    /**
     * @return list<string>
     */
    private function knownCredentialEmails(): array
    {
        return [
            'admin@maeyangha.test',
            'member@maeyangha.test',
            'user_test@khaisaeng.test',
            'admin_test@khaisaeng.test',
            'sbadmin_test@khaisaeng.test',
        ];
    }
}
