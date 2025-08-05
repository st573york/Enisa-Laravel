<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Administrator role with full permissions on data for all countries', 'order' => 1],
            ['name' => 'PoC', 'description' => 'Country Point of Contact with full permissions on managing users and data for the associated country only', 'order' => 3],
            ['name' => 'operator', 'description' => 'Country Operator with full permissions on data for the associated country only', 'order' => 4],
            ['name' => 'viewer', 'description' => 'Country viewer with read-only permissions on data for the associated country only', 'order' => 5],
            ['name' => 'Primary PoC', 'description' => 'Country Primary Point of Contact with full permissions on managing users and data for the associated country only', 'order' => 2]
        ];

        foreach($roles as $role) {
            Role::updateOrCreate(
                [
                    'name' => $role['name']
                ],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'order' => $role['order']
                ]
            );
        }
    }
}
