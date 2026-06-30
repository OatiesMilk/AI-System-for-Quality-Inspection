<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Defect extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_id',
        'defect_type',
        'confidence_score',
        'bounding_box',
        'confirmed',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:4',
            'bounding_box' => 'array',
            'confirmed' => 'boolean',
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }
}
