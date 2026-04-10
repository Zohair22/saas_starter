<?php

namespace Modules\User\Providers;

use Modules\User\Interfaces\Contracts\UserRepositoryInterface;
use Modules\User\Interfaces\Contracts\UserServiceInterface;
use Modules\User\Repositories\UserRepository;
use Modules\User\Services\UserService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class UserServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'User';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'user';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    /**
     * Register module bindings.
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );
        $this->app->bind(
            UserServiceInterface::class,
            UserService::class
        );
    }
}
