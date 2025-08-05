<?php

namespace Database\Seeders;

use App\Models\InvitationStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InvitationStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'pending', 'description' => 'Invitation is pending - user is not registered yet', 'order' => 1],
            ['name' => 'registered', 'description' => 'User has been successfully registered', 'order' => 2],
            ['name' => 'expired', 'description' => 'Invitation has expired', 'order' => 3]
        ];

        foreach($statuses as $status) {
            InvitationStatus::create([
                'name' => $status['name'],
                'description' => $status['description'],
                'order' => $status['order'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
