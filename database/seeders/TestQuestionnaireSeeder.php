<?php

namespace Database\Seeders;

use App\Models\Questionnaire;
use App\Models\User;
use App\Models\IndexConfiguration;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestQuestionnaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $years = (getenv('YEARS_SEEDER') !== false) ? preg_split('/ |, |,/', env('YEARS_SEEDER')) : config('constants.LAST_2_YEARS');

        $user = User::with('permissions')->whereHas('permissions', function ($query) {
            $query->where('role_id', 1);
        })->first();

        foreach($years as $year)
        {
            $index_data = IndexConfiguration::getExistingPublishedConfigurationForYear($year);
            
            Questionnaire::create([
                'title' => 'MS Survey ' . $index_data->year,
                'description' =>
                    '<div class="json-scope">
                        <p>ENISA has been working since last year on the development of a EU Cybersecurity Index, a tool to help Member States
                            making informed decisions by providing insights on the cybersecurity maturity and posture of the Union and MS
                            policies, capabilities and operations.
                        </p>
                        <p><strong>In 2021, we have developed the framework for the index, meaning a list of 58 composite indicators in four
                            areas: policy, operations, capacity and market/industry. This year, among other things, we have examined
                            each indicator to identify its data source. Based on data availability some indicators have been updated, others
                            have been temporarily discontinued and others have been added.&nbsp;</strong>
                        </p>
                        <p>The purpose of this document is to give <strong>a status update on the 2022 pilot index and its structure, as well as
                            the list of indicators that are part of the index as per the aforementioned discussion.</strong>
                        </p>
                        <p>In particular, this document provides an overview of indicators that are:</p>
                        <ul>
                            <li><strong><u>Part of the pilot</u>: 52</strong> indicators that are used to calculate the value of the index.
                                35 have been selected among the 58 composite indicators identified in 2021; 17 are new. They are described
                                respectively <strong>section 2</strong> and <strong>section 3</strong>.
                            </li>
                            <li><strong><u>Context indicators</u>: 5&nbsp;</strong>indicators that are used for the calculations of some
                                indicators (e.g. population) and/or to give contextual data (e.g. eGovernment benchmark) <strong>(Section 4)</strong>.
                            </li>
                            <li><strong><u>Frozen:</u> 10</strong> indicators, among the 58 composite indicators, that have been discontinued
                                temporarily as they rely on the outcome of pending discussions <strong>(Section 5)</strong>.
                            </li>
                            <li><strong><u>Discontinued</u>: 12</strong> indicators, among the 58 composite indicators, that were deemed not
                                to fit the purpose of the index e.g. due to lack of data or repetition <strong>(Section 6)</strong>.
                            </li>
                        </ul>
                        <p>Finally, the document provides a consolidated update of the indicators&rsquo; subdomains, areas and weights <strong>(section 7)</strong>.</p>
                    </div>',
                'user_id' => $user->id,
                'index_configuration_id' => $index_data->id,
                'year' => $index_data->year,
                'deadline' => Carbon::now()->addMonths(3),
                'published' => true,
                'published_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            Questionnaire::create([
                'title' => 'Test Questionnaire',
                'user_id' => $user->id,
                'index_configuration_id' => $index_data->id,
                'year' => $index_data->year,
                'deadline' => Carbon::now()->addMonths(3),
                'published' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
