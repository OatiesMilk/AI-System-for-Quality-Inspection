<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Defect;
use App\Models\Inspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspectionIngestController extends Controller
{
    /**
     * Ingest a single inspection event from the computer vision pipeline.
     *
     * Expects a multipart/form-data request:
     * - batch_id: int, required, must reference an existing batch
     * - checkpoint: string, required, "preparation" or "finishing"
     * - image: file, required, the captured inspection photo
     * - defects: array, optional, each item:
     *     - defect_type: string, one of the known defect enum values
     *     - confidence_score: float, 0-1 (YOLO confidence)
     *     - bounding_box: array with x, y, width, height as fractions of image size (0-1)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'checkpoint' => ['required', 'in:preparation,finishing'],
            'image' => ['required', 'image', 'max:10240'],
            'defects' => ['array'],
            'defects.*.defect_type' => ['required_with:defects', 'in:scratch,cut,hole,crease,excess_glue,excess_stitch'],
            'defects.*.confidence_score' => ['required_with:defects', 'numeric', 'between:0,1'],
            'defects.*.bounding_box' => ['nullable', 'array'],
            'defects.*.bounding_box.x' => ['required_with:defects.*.bounding_box', 'numeric', 'between:0,1'],
            'defects.*.bounding_box.y' => ['required_with:defects.*.bounding_box', 'numeric', 'between:0,1'],
            'defects.*.bounding_box.width' => ['required_with:defects.*.bounding_box', 'numeric', 'between:0,1'],
            'defects.*.bounding_box.height' => ['required_with:defects.*.bounding_box', 'numeric', 'between:0,1'],
        ]);

        $imageFile = $request->file('image');
        $imageData = base64_encode(file_get_contents($imageFile->getRealPath()));
        $imageMime = $imageFile->getMimeType();

        $inspection = DB::transaction(function () use ($validated, $imageData, $imageMime) {
            $inspection = Inspection::create([
                'batch_id' => $validated['batch_id'],
                'checkpoint' => $validated['checkpoint'],
                'image_data' => $imageData,
                'image_mime' => $imageMime,
            ]);

            foreach ($validated['defects'] ?? [] as $defect) {
                Defect::create([
                    'inspection_id' => $inspection->id,
                    'defect_type' => $defect['defect_type'],
                    'confidence_score' => $defect['confidence_score'],
                    'bounding_box' => $defect['bounding_box'] ?? null,
                ]);
            }

            return $inspection;
        });

        AuditLog::record('inspection.ingested', $request->user(), [
            'inspection_id' => $inspection->id,
            'batch_id' => $inspection->batch_id,
            'defect_count' => count($validated['defects'] ?? []),
        ]);

        return response()->json([
            'message' => 'Inspection recorded.',
            'inspection_id' => $inspection->id,
            'defect_count' => $inspection->defects()->count(),
        ], 201);
    }
}
