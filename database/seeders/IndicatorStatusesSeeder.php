<?php

namespace Database\Seeders;

use App\Models\IndicatorStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IndicatorStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'unassigned', 'description' => 'Indicator is not yet assigned to any user', 'order' => 1],
            ['name' => 'assigned', 'description' => 'Indicator is assigned to a user', 'order' => 2],
            ['name' => 'saved', 'description' => 'Indicator is saved/in progress', 'order' => 3],
            ['name' => 'submitted', 'description' => 'Indicator is submitted', 'order' => 4],
            ['name' => 'request_changes', 'description' => 'Indicator has been requested changes but not yet sent', 'order' => 5],
            ['name' => 'requested_changes', 'description' => 'Indicator has been requested changes', 'order' => 6],
            ['name' => 'approved', 'description' => 'Indicator is approved', 'order' => 7],
            ['name' => 'final_approved', 'description' => 'Indicator is final approved', 'order' => 8]
        ];
        
        foreach($statuses as $status) {
            IndicatorStatus::create([
                'name' => $status['name'],
                'description' => $status['description'],
                'order' => $status['order'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
