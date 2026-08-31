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
            $this->command?->warn('Demo accounts not found - run the main DatabaseSeeder first.');

            return;
        }

        $batchPlans = [
            ['expected_pieces' => 40, 'stage' => 'pre_assembly', 'days_ago' => 0, 'status' => 'open'],
            ['expected_pieces' => 35, 'stage' => 'pre_assembly', 'days_ago' => 0, 'status' => 'open'],
            ['expected_pieces' => 50, 'stage' => 'pre_assembly', 'days_ago' => 1, 'status' => 'completed'],
            ['expected_pieces' => 30, 'stage' => 'preparation', 'days_ago' => 1, 'status' => 'open'],
            ['expected_pieces' => 45, 'stage' => 'preparation', 'days_ago' => 2, 'status' => 'completed'],
        ];

        foreach ($batchPlans as $index => $plan) {
            $date = now()->subDays($plan['days_ago']);

            $batch = Batch::create([
                'batch_code' => 'BATCH-'.$date->format('Ymd').'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'production_date' => $date,
                'expected_pieces' => $plan['expected_pieces'],
                'status' => $plan['status'],
                'manufacturing_stage' => $plan['stage'],
                'created_by' => $manager->id,
            ]);

            AuditLog::record('batch.created', $manager, [
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'expected_pieces' => $batch->expected_pieces,
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

            // One inspection flagged for rework - alternate through each progress state.
            $stations = ['cutting', 'marking', 'skiving', 'upper_making'];
            $constructor = User::where('email', 'constructor@cpoint.test')->first();
            $isResolved = $index % 2 === 0;
            $reworkStatus = $isResolved ? 'completed' : ($index % 4 === 1 ? 'in_progress' : 'not_started');

            $rework = Inspection::factory()->for($batch)->reviewed('rework')->create([
                'checkpoint' => $plan['stage'],
                'inspector_id' => $inspector->id,
                'rework_station' => $stations[$index % count($stations)],
                'rework_status' => $reworkStatus,
                'reworked_at' => $isResolved ? now()->subHours(3) : null,
                'resolved_by' => $isResolved ? $constructor?->id : null,
            ]);
            Defect::factory()->for($rework)->create(['confirmed' => true]);

            AuditLog::record('inspection.validated', $inspector, [
                'inspection_id' => $rework->id,
                'action' => 'rework',
                'rework_station' => $rework->rework_station,
                'ai_override' => false,
            ]);

            if ($rework->reworked_at) {
                AuditLog::record('inspection.reworked', $constructor, [
                    'inspection_id' => $rework->id,
                ]);
            }
        }
    }
}
