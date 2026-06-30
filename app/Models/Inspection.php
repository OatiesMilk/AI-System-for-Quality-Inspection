<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'inspector_id',
        'checkpoint',
        'image_path',
        'action',
        'ai_override',
        'inspected_at',
        'reworked_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_override' => 'boolean',
            'inspected_at' => 'datetime',
            'reworked_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function defects(): HasMany
    {
        return $this->hasMany(Defect::class);
    }
}
