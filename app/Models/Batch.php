<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'production_date',
        'expected_pieces',
        'status',
        'manufacturing_stage',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * Auto-complete this batch if its produced piece count has reached
     * expected_pieces. Called after every inspection ingest so the "latest
     * open batch" queue lookup naturally moves on to the next batch without
     * needing manual coordination. No-ops for batches with no expected
     * count set, or that are already completed.
     */
    public function completeIfThresholdReached(): void
    {
        if ($this->status === 'completed' || $this->expected_pieces === null) {
            return;
        }

        if ($this->inspections()->count() >= $this->expected_pieces) {
            $this->update(['status' => 'completed']);
        }
    }
}
