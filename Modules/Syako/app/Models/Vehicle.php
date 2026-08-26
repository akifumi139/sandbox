<?php

namespace Modules\Syako\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Syako\Database\Factories\VehicleFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $vehicle_number
 * @property string|null $manufacturer
 * @property string|null $model
 * @property string|null $model_code
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Booking> $bookings
 */
#[Table('syako__vehicles')]
#[Fillable(['name', 'vehicle_number', 'manufacturer', 'model', 'model_code', 'is_active'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $attributes = [
        'is_active' => true,
    ];

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): VehicleFactory
    {
        return VehicleFactory::new();
    }
}
