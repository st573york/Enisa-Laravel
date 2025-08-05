<?php

namespace Database\Seeders;

use App\HelperFunctions\TaskHelper;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class InsertIndicatorValuesCalculationTask extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $index_calculation_task = TaskHelper::getTask([
            'type' => 'IndexCalculation'
        ]);
        
        TaskHelper::updateOrCreateTask([
            'type' => 'IndicatorValuesCalculation',
            'user_id' => $index_calculation_task->user_id,
            'status_id' => 2,
            'index_configuration_id' => $index_calculation_task->index_configuration_id,
            'payload' => [
                'last_indicator_values_calculation_by' => $index_calculation_task->user_id,
                'last_indicator_values_calculation_at' => Carbon::createFromFormat('Y-m-d H:i:s', $index_calculation_task->created_at)->format('d-m-Y H:i:s')
            ]
        ]);
    }
}
