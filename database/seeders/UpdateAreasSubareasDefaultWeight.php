<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Subarea;
use Illuminate\Database\Seeder;

class UpdateAreasSubareasDefaultWeight extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Area::query()->update(['default_weight' => 1]);
        Subarea::query()->update(['default_weight' => 1]);
    }
}
