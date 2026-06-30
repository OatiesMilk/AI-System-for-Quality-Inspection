<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchLookupController extends Controller
{
    /**
     * Resolve the most recently created batch matching a manufacturing stage
     * (and optionally a shift), so the computer vision pipeline can always
     * target whichever batch is currently open without being told a fixed
     * batch_id up front. Lets a live capture session automatically pick up
     * a newly created batch instead of needing to be restarted.
     */
    public function latest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'checkpoint' => ['required', 'in:preparation,finishing'],
            'shift' => ['nullable', 'in:am,pm'],
        ]);

        $batch = Batch::query()
            ->where('manufacturing_stage', $validated['checkpoint'])
            ->when($request->filled('shift'), fn ($query) => $query->where('shift', $validated['shift']))
            ->latest('id')
            ->first();

        if (! $batch) {
            return response()->json([
                'message' => 'No matching batch found.',
            ], 404);
        }

        return response()->json([
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'shift' => $batch->shift,
            'manufacturing_stage' => $batch->manufacturing_stage,
        ]);
    }
}
