<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function create(): View
    {
        return view('dashboards.manager-batch-create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_code' => ['required', 'string', 'max:255', 'unique:batches,batch_code'],
            'production_date' => ['required', 'date'],
            'expected_pieces' => ['required', 'integer', 'min:1'],
            'manufacturing_stage' => ['required', 'in:preparation,pre_assembly'],
        ]);

        $batch = Batch::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record('batch.created', $request->user(), [
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'expected_pieces' => $batch->expected_pieces,
        ]);

        return redirect()->route('dashboard.manager')
            ->with('status', "Batch {$batch->batch_code} created.");
    }

    public function edit(Batch $batch): View
    {
        return view('dashboards.manager-batch-edit', compact('batch'));
    }

    public function update(Request $request, Batch $batch): RedirectResponse
    {
        $validated = $request->validate([
            'batch_code' => ['required', 'string', 'max:255', 'unique:batches,batch_code,'.$batch->id],
            'production_date' => ['required', 'date'],
            'expected_pieces' => ['required', 'integer', 'min:1'],
            'manufacturing_stage' => ['required', 'in:preparation,pre_assembly'],
        ]);

        $batch->update($validated);

        AuditLog::record('batch.updated', $request->user(), [
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
        ]);

        return redirect()->route('dashboard.manager')
            ->with('status', "Batch {$batch->batch_code} updated.");
    }

    /**
     * Manually close a batch's intake regardless of its current produced
     * count. Needed for the under-target case (a batch that will never
     * auto-complete because some expected pieces were damaged/discarded and
     * never made it to the line) so the vision pipeline's FIFO queue isn't
     * left waiting forever and can move on to the next batch.
     */
    public function close(Request $request, Batch $batch): RedirectResponse
    {
        $produced = $batch->inspections()->count();

        $batch->update(['status' => 'completed']);

        AuditLog::record('batch.closed', $request->user(), [
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'expected_pieces' => $batch->expected_pieces,
            'produced' => $produced,
        ]);

        return redirect()->route('dashboard.manager')
            ->with('status', "Batch {$batch->batch_code} closed ({$produced}/{$batch->expected_pieces} pieces).");
    }
}
