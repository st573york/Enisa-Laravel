<?php

namespace Database\Seeders;

use App\Models\IndicatorRequestedChangeStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RequestedChangesStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'pending', 'description' => 'Requested changes are pending and not yet sent', 'order' => 1],
            ['name' => 'submitted', 'description' => 'Requested changes are submitted and sent to a user', 'order' => 2]
        ];
        
        foreach($statuses as $status) {
            IndicatorRequestedChangeStatus::create([
                'name' => $status['name'],
                'description' => $status['description'],
                'order' => $status['order'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
