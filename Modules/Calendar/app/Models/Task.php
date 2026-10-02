<?php

namespace Modules\Calendar\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Calendar\Database\Factories\TaskFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property array<int, string>|null $assignees
 * @property string $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Table('calendar__tasks')]
#[Fillable(['user_id', 'name', 'start_date', 'end_date', 'start_time', 'end_time', 'assignees', 'color'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $attributes = [
        'assignees' => '[]',
    ];

    /** @param Builder<Task> $query */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeLabel(): ?string
    {
        if ($this->start_time === null || $this->end_time === null) {
            return null;
        }

        return substr($this->start_time, 0, 5).' - '.substr($this->end_time, 0, 5);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'assignees' => 'array',
        ];
    }

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }
}
