<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $statuses = [
            ['name' => 'new', 'description' => 'Index is not yet submitted for verifaction', 'order' => 1],
            ['name' => 'pending verification', 'description' => 'Index pending verification', 'order' => 2],
            ['name' => 'verified', 'description' => 'Index is verified and ready to be published', 'order' => 3],
            ['name' => 'published', 'description' => 'Index is published', 'order' => 4],
        ];

        foreach ($statuses as $status) {
            DB::table('index_statuses')->insert([
                'name' => $status['name'],
                'description' => $status['description'],
                'order' => $status['order'],
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s"),
            ]);
        }
    }
}
