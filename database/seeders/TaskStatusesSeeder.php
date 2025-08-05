<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TaskStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $statuses = [
            ['name' => 'In Progress', 'description' => 'Task is in progress', 'order' => 1],
            ['name' => 'Completed', 'description' => 'Task is completed', 'order' => 2],
            ['name' => 'Failed', 'description' => 'Task failed to be completed', 'order' => 3],
            ['name' => 'Approved', 'description' => 'Task is approved', 'order' => 4]
        ];
        
        foreach ($statuses as $status) {
            TaskStatus::create([
                'name' => $status['name'],
                'description' => $status['description'],
                'order' => $status['order'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
