<?php

namespace Modules\Syako\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Syako\Models\Booking;
use Modules\Syako\Models\Vehicle;
use Modules\Syako\Policies\BookingPolicy;
use Modules\Syako\Policies\VehiclePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SyakoServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Syako';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'syako';

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

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
