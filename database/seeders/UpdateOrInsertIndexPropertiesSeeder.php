<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Indicator;
use App\Models\IndicatorDisclaimer;
use App\Models\Subarea;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Exception;

class UpdateOrInsertIndexPropertiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try
        {
            $years = (getenv('YEARS_SEEDER') !== false) ? preg_split('/ |, |,/', env('YEARS_SEEDER')) : config('constants.LAST_2_YEARS');
            
            foreach($years as $year)
            {
                $file = __DIR__ . '/Importers/import-files/' . $year . '/Index-Properties-Updates-Inserts.xlsx';
                
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $spreadsheet = $reader->load($file);

                $subarea_to_update = Subarea::where('identifier', 12)->where('year', $year)->first();
                Indicator::where('identifier', 44)->where('year', $year)->update(
                    [
                        'default_subarea_id' => $subarea_to_update->id
                    ]
                );

                $subarea_to_delete = Subarea::where('identifier', 9)->where('year', $year)->first();
                Subarea::where('identifier', $subarea_to_delete->identifier)->where('year', $year)->delete();

                $sheet = $spreadsheet->getSheetByName('Areas');
                $this->updateAreas($sheet, $year);

                $sheet = $spreadsheet->getSheetByName('Subareas');
                $this->updateSubareas($sheet, $year);

                $sheet = $spreadsheet->getSheetByName('Indicators-update');
                $this->updateIndicators($sheet, $year);

                $sheet = $spreadsheet->getSheetByName('Indicators-insert');
                $this->insertIndicators($sheet, $year);
            }
        }
        catch (Exception $e)
        {
            DB::rollback();

            print('Error: ' . $e->getMessage() . "\n");
        }

        DB::commit();
    }

    private function updateAreas($sheet, $year)
    {
        $rows = array_slice($sheet->toArray(), 1);

        foreach($rows as $row) {
            Area::where('identifier', $row[3])->where('year', $year)->update(
                [
                    'description' => $row[2]
                ]
            );
        }
    }

    private function updateSubareas($sheet, $year)
    {
        $rows = array_slice($sheet->toArray(), 1);

        foreach($rows as $row) {
            Subarea::where('identifier', $row[4])->where('year', $year)->update(
                [
                    'name' => $row[1],
                    'short_name' => $row[2],
                    'description' => $row[3],
                    'identifier' => $row[5]
                ]
            );
        }
    }

    private function updateIndicators($sheet, $year)
    {
        $rows = array_slice($sheet->toArray(), 1);

        foreach($rows as $row)
        {
            $indicator = Indicator::where('identifier', $row[4])->where('year', $year)->first();
            $indicator->update(
                [
                    'name' => $row[1],
                    'short_name' => $row[2],
                    'description' => $row[3],
                    'algorithm' => $row[7],
                    'report_year' => $row[15],
                    'default_weight' => $row[17]
                ]
            );
                
            IndicatorDisclaimer::where('indicator_id', $indicator->id)->update(
                [
                    'what_100_means_eu' => $row[18],
                    'what_100_means_ms' => $row[19],
                    'frac_max_norm' => filter_var($row[20], FILTER_VALIDATE_BOOLEAN),
                    'rank_norm' => filter_var($row[21], FILTER_VALIDATE_BOOLEAN),
                    'target_100' => filter_var($row[22], FILTER_VALIDATE_BOOLEAN),
                    'target_75' => filter_var($row[23], FILTER_VALIDATE_BOOLEAN)
                ]
            );
        }
    }

    private function insertIndicators($sheet, $year)
    {
        $rows = array_slice($sheet->toArray(), 1);

        foreach($rows as $row)
        {
            $id = $row[0];
            $category = $row[6];
            $subarea = $row[16];

            if ($category != 'eu-wide')
            {
                if (!strlen(trim($subarea))) {
                    throw new Exception("Subarea name is missing. Please check id '{$id}' in the Indicators sheet!");
                }

                $dbSubarea = Subarea::where('name', $subarea)->where('year', $year)->first();

                if (is_null($dbSubarea)) {
                    throw new Exception("Subarea name '{$subarea}' was not found in the Subareas sheet. Please check id '{$id}' in the Indicators sheet!");
                }
            }

            $indicator = Indicator::create(
                [
                    'name' => $row[1],
                    'short_name' => $row[2],
                    'description' => $row[3],
                    'identifier' => $row[4],
                    'source' => $row[5],
                    'category' => $category,
                    'algorithm' => $row[7],
                    'report_year' => $row[15],
                    'default_subarea_id' => ($category != 'eu-wide') ? $dbSubarea->id : null,
                    'default_weight' => $row[17],
                    'year' => $year
                ]
            );
                
            IndicatorDisclaimer::create(
                [
                    'indicator_id' => $indicator->id,
                    'what_100_means_eu' => $row[18],
                    'what_100_means_ms' => $row[19],
                    'frac_max_norm' => filter_var($row[20], FILTER_VALIDATE_BOOLEAN),
                    'rank_norm' => filter_var($row[21], FILTER_VALIDATE_BOOLEAN),
                    'target_100' => filter_var($row[22], FILTER_VALIDATE_BOOLEAN),
                    'target_75' => filter_var($row[23], FILTER_VALIDATE_BOOLEAN),
                    'direction' => $row[8],
                    'new_indicator' => $row[9],
                    'min_max_0037_1' => $row[10],
                    'min_max' => $row[11]
                ]
            );
        }
    }
}
