<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            CountriesSeeder::class,
            RolesSeeder::class,
            IndicatorStatusesSeeder::class,
            InvitationStatusesSeeder::class,
            RequestedChangesStatusesSeeder::class,

            TestUsersSeeder::class,
            TestPermissionsSeeder::class,

            StatusesSeeder::class,
            TaskStatusesSeeder::class,
            IndicatorQuestionTypesSeeder::class,
            IndicatorQuestionChoicesSeeder::class,

            IndexPropertiesWithSurveySeeder::class,
            UpdateOrInsertIndexPropertiesSeeder::class,
            TestIndexConfigurationsSeeder::class,
            TestIndicatorValuesSeeder::class,

            TestQuestionnaireSeeder::class,
            TestQuestionnaireIndicatorsSeeder::class,
            TestIndexInstancesSeeder::class,
            TestQuestionnaireUsersSeeder::class,
            TestIndexReportSeeder::class
        ]);
    }
}
