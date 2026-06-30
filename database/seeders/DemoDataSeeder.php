<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Defect;
use App\Models\Inspection;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed batches, inspections, defects, and audit log entries for the four
     * demo accounts so every dashboard has realistic data to demonstrate.
     */
    public function run(): void
    {
        $manager = User::where('email', 'manager@cpoint.test')->first();
        $inspector = User::where('email', 'inspector@cpoint.test')->first();

        if (! $manager || ! $inspector) {
            $this->command?->warn('Demo accounts not found — run the main DatabaseSeeder first.');

            return;
        }

        $batchPlans = [
            ['shift' => 'am', 'stage' => 'finishing', 'days_ago' => 0],
            ['shift' => 'pm', 'stage' => 'finishing', 'days_ago' => 0],
            ['shift' => 'am', 'stage' => 'finishing', 'days_ago' => 1],
            ['shift' => 'pm', 'stage' => 'preparation', 'days_ago' => 1],
            ['shift' => 'am', 'stage' => 'preparation', 'days_ago' => 2],
        ];

        foreach ($batchPlans as $index => $plan) {
            $batchNumber = $plan['shift'] === 'am' ? 1 : 2;
            $date = now()->subDays($plan['days_ago']);

            $batch = Batch::create([
                'batch_code' => 'BATCH-'.$batchNumber.'-'.$date->format('Ymd').'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'production_date' => $date,
                'shift' => $plan['shift'],
                'manufacturing_stage' => $plan['stage'],
                'created_by' => $manager->id,
            ]);

            AuditLog::record('batch.created', $manager, [
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'shift' => $batch->shift,
            ]);

            // One inspection still awaiting HITL validation.
            $pending = Inspection::factory()->for($batch)->create([
                'checkpoint' => $plan['stage'],
            ]);
            Defect::factory()->for($pending)->create();

            // One inspection already reviewed and passed.
            $passed = Inspection::factory()->for($batch)->reviewed('pass')->create([
                'checkpoint' => $plan['stage'],
                'inspector_id' => $inspector->id,
            ]);
            Defect::factory()->for($passed)->create(['confirmed' => true]);

            AuditLog::record('inspection.validated', $inspector, [
                'inspection_id' => $passed->id,
                'action' => 'pass',
                'ai_override' => false,
            ]);

            // One inspection flagged for rework — alternate resolved/unresolved.
            $rework = Inspection::factory()->for($batch)->reviewed('rework')->create([
                'checkpoint' => $plan['stage'],
                'inspector_id' => $inspector->id,
                'reworked_at' => $index % 2 === 0 ? now()->subHours(3) : null,
            ]);
            Defect::factory()->for($rework)->create(['confirmed' => true]);

            AuditLog::record('inspection.validated', $inspector, [
                'inspection_id' => $rework->id,
                'action' => 'rework',
                'ai_override' => false,
            ]);

            if ($rework->reworked_at) {
                $constructor = User::where('email', 'constructor@cpoint.test')->first();

                AuditLog::record('inspection.reworked', $constructor, [
                    'inspection_id' => $rework->id,
                ]);
            }
        }
    }
}
