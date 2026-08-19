<?php

namespace Modules\Souko\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('souko_tool_logs')]
#[Fillable(['tool_id', 'action_type', 'user_name', 'logged_at', 'note'])]
class ToolLog extends Model
{
    use HasFactory;

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'tool_id');
    }

    protected function loggedAt(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => CarbonImmutable::parse($value),
        );
    }
}
