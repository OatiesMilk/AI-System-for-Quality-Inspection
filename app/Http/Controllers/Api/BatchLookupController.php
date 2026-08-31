<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchLookupController extends Controller
{
    /**
     * Resolve the batch the vision pipeline should currently be targeting
     * for a manufacturing stage: the oldest still-open batch for that
     * checkpoint (a FIFO queue), not just whichever batch row is newest.
     * This is what lets multiple batches be simultaneously in flight at
     * different stations - e.g. a cutter starting batch 2 while batch 1 is
     * still being checked further down the line - without the pipeline
     * ever attributing a piece to the wrong batch. A batch stops being
     * eligible here the moment it's completed (auto, once its produced
     * count reaches expected_pieces, or manually closed by a manager), so
     * the very next capture automatically picks up the next batch in the
     * queue with no restart or manual coordination needed.
     */
    public function latest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'checkpoint' => ['required', 'in:preparation,pre_assembly'],
        ]);

        $batch = Batch::query()
            ->where('manufacturing_stage', $validated['checkpoint'])
            ->where('status', 'open')
            ->oldest('id')
            ->first();

        if (! $batch) {
            return response()->json([
                'message' => 'No matching batch found.',
            ], 404);
        }

        return response()->json([
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'manufacturing_stage' => $batch->manufacturing_stage,
        ]);
    }

    /**
     * Let the operator running the capture station close out the batch
     * they're currently working, the moment they physically know it's
     * done - regardless of whether the piece count landed exactly on
     * expected_pieces. They're the one who actually knows a roll is
     * finished, not the manager watching a dashboard, so this is the
     * primary way batches get closed; the manager's own close action is
     * just a fallback for when this didn't happen.
     */
    public function close(Request $request, Batch $batch): JsonResponse
    {
        $produced = $batch->forceClose();

        AuditLog::record('batch.closed', $request->user(), [
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'expected_pieces' => $batch->expected_pieces,
            'produced' => $produced,
            'closed_via' => 'vision_pipeline',
        ]);

        return response()->json([
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'status' => $batch->status,
            'expected_pieces' => $batch->expected_pieces,
            'produced' => $produced,
        ]);
    }
}
