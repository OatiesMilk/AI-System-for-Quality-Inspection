<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Response;

class InspectionImageController extends Controller
{
    /**
     * Stream an inspection's image straight out of the database, so it's
     * visible from any machine on the shared MySQL connection without
     * needing a separate shared filesystem.
     */
    public function show(Inspection $inspection): Response
    {
        abort_unless($inspection->hasStoredImage(), 404);

        return response(
            base64_decode($inspection->image_data),
            200,
            ['Content-Type' => $inspection->image_mime ?? 'image/jpeg']
        );
    }
}
