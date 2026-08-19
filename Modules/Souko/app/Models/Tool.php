<?php

namespace Modules\Souko\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Souko\Database\Factories\ToolFactory;

#[Table('souko__tools')]
#[
    Fillable([
        'management_number',
        'name',
        'model',
        'manufacturer',
        'status',
        'note',
    ])
]
class Tool extends Model
{
    use HasFactory;

    protected static function newFactory(): ToolFactory
    {
        return ToolFactory::new();
    }
}
