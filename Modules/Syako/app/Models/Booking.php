<?php

namespace Modules\Syako\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Syako\Database\Factories\BookingFactory;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property int $user_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $notes
 * @property bool $has_fuel_card
 * @property bool $has_etc_card
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Vehicle $vehicle
 * @property-read User $user
 */
#[Table('syako__bookings')]
#[Fillable(['vehicle_id', 'user_id', 'starts_at', 'ends_at', 'notes', 'has_fuel_card', 'has_etc_card'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAllDay(): bool
    {
        return $this->starts_at->format('H:i') === '00:00'
            && $this->ends_at->format('H:i') === '00:00';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'has_fuel_card' => 'boolean',
            'has_etc_card' => 'boolean',
        ];
    }

    protected static function newFactory(): BookingFactory
    {
        return BookingFactory::new();
    }
}
