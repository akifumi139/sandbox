<?php

namespace Modules\Heya\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Heya\Models\Booking;
use Modules\Heya\Models\Room;
use Modules\Heya\Policies\BookingPolicy;
use Modules\Heya\Policies\RoomPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class HeyaServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Heya';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'heya';

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
        Gate::policy(Room::class, RoomPolicy::class);
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
