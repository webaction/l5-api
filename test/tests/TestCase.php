<?php

namespace Specialtactics\L5Api\Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use JWTAuth;
use Orchestra\Testbench\TestCase as BaseTestCase;
use UserStorySeeder;

class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Do migrations for tests
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $factory = app(EloquentFactory::class);
        $factory->load(__DIR__ . '/../database/factories');

        $this->artisan('migrate', ['--database' => 'testing', '--seed' => true]);
    }



    public function tearDown(): void
    {
        //m::close();
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // API Config
        $app['config']->set('api', include __DIR__ . '/../config/api.php');
        $app['config']->set('auth', include __DIR__ . '/../config/auth.php');
        $app['config']->set('jwt', include __DIR__ . '/../config/jwt.php');
    }

    /**
     * Set the service providers of this package
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Tymon\JWTAuth\Providers\LaravelServiceProvider::class,
            \Dingo\Api\Provider\LaravelServiceProvider::class,
            \Specialtactics\L5Api\L5ApiServiceProvider::class,
            \Specialtactics\L5Api\Test\Mocks\AppServiceProvider::class,
            \Specialtactics\L5Api\Test\Mocks\RouteServiceProvider::class,
        ];
    }

    /**
     * Set the service providers of this package
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app) {
        // Will be useful later
        return [
            'API' => \Dingo\Api\Facade\API::class,
            'JWTAuth' => \Tymon\JWTAuth\Facades\JWTAuth::class,
        ];
    }

    /**
     * Resolve application HTTP Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton(\Illuminate\Contracts\Http\Kernel::class, \App\Http\Kernel::class);
    }

    /**
     * Set the currently logged in user for the application and Authorization headers for API request
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable
     * @param  string|null  $driver
     * @return $this
     */
    public function actingAs(UserContract $user, $driver = null)
    {
        parent::actingAs($user, $driver);

        return $this->withHeader('Authorization', 'Bearer ' . JWTAuth::fromUser($user));
    }

    /**
     * API Test case helper function for setting up
     * the request auth header as supplied user
     *
     * @param array $credentials
     * @return $this
     */
    public function actingAsUser($credentials)
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            return $this;
        }

        $user = ($apiKey = Arr::get($credentials, 'api_key'))
            ? User::whereApiKey($apiKey)->firstOrFail()
            : User::whereEmail(Arr::get($credentials, 'email'))->firstOrFail();

        return $this->actingAs($user);
    }

    /**
     * API Test case helper function for setting up the request as a logged in admin user
     *
     * @return $this
     */
    public function actingAsAdmin()
    {
        $user = User::where('email', UserStorySeeder::ADMIN_CREDENTIALS[0])->firstOrFail();

        return $this->actingAs($user);
    }
}