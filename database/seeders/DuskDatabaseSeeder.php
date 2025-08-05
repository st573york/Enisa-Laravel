<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DuskDatabaseSeeder extends Seeder
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
            // UpdateOrInsertIndexPropertiesSeeder::class,
            TestIndexConfigurationsSeeder::class,
            TestIndicatorValuesSeeder::class,

            TestQuestionnaireSeeder::class,
            DuskQuestionnaireIndicatorsSeeder::class,
            TestIndexInstancesSeeder::class,
            TestIndexReportSeeder::class
        ]);
    }
}
