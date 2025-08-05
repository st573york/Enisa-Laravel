<?php

namespace App\Exports;

use App\Models\Indicator;
use App\Models\IndicatorAccordionQuestion;
use App\Models\SurveyIndicator;
use App\Models\SurveyIndicatorAnswer;
use App\Models\SurveyIndicatorOption;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class SurveyIndicatorTemplateExport implements FromView, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    const DNA = 'Data not available/Not willing to share';

    private $questionnaire_country;
    private $indicatorData;

    public function __construct($questionnaire_country, $indicatorData)
    {
        $this->questionnaire_country = $questionnaire_country;
        $this->indicatorData = $indicatorData;
    }

    public function view(): View
    {
        return view(
            'export.survey-indicator-template',
            ['data' => $this->indicatorData]
        );
    }

    public function title(): string
    {
        return $this->indicatorData['number'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:C')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B:C')->getAlignment()->setWrapText(true);
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1:C1')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 100,
            'C' => 20
        ];
    }

    public function registerEvents(): array
    {
        return [
            // handle by a closure.
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                // Protection
                $protection = $sheet->getProtection();
                $protection->setSheet(true);
                // Hide columns
                $sheet->getColumnDimension('D')->setVisible(false);
                $sheet->getColumnDimension('E')->setVisible(false);
                $sheet->getColumnDimension('Y')->setVisible(false);
                $sheet->getColumnDimension('Z')->setVisible(false);

                $accordion = 0;
                $row_count = 3;
                $option_count = 0;

                $identifier = $this->indicatorData['identifier'];
                $with_answers = (!is_null($this->questionnaire_country)) ? true : false;

                if ($with_answers)
                {
                    $indicator = Indicator::where('identifier', $identifier)->where('year', $this->questionnaire_country->questionnaire->year)->first();
                    $survey_indicator = SurveyIndicator::getSurveyIndicator($this->questionnaire_country, $indicator);
                }

                $sheet->setCellValue('D1', $identifier);
                
                foreach ($this->indicatorData['questions'] as $questionId => $questionData)
                {
                    $parts = explode('-', $questionId);

                    $question = IndicatorAccordionQuestion::find($questionData['id']);
                    
                    if ($with_answers)
                    {
                        $survey_indicator_answer = SurveyIndicatorAnswer::getSurveyIndicatorAnswer($survey_indicator, $question);
                        $survey_indicator_options = SurveyIndicatorOption::getSurveyIndicatorOptions($survey_indicator, $question);
                    }
                    
                    if ($accordion != $parts[1]) {
                        $row_count++;
                    }
                    
                    $data = [
                        'cell' => '',
                        'options' => [],
                        'options_with_comma' => false,
                        'protection' => false
                    ];

                    // Choose answer - Data not available/Not willing to share
                    $cell_num = $row_count;
                    $data['cell'] = 'C' . $cell_num;
                    $data['options'] = [($questionData['type'] == 'free-text' ? 'Provide your answer' : 'Choose answer'), self::DNA];
                    if ($with_answers &&
                        !is_null($survey_indicator_answer) &&
                        $survey_indicator_answer->choice_id == 3)
                    {
                        $sheet->setCellValue($data['cell'], self::DNA);
                    }

                    self::setCellDropDown($sheet, $data);
                    self::setCellProtection($sheet, $data);

                    // Question info
                    $sheet->setCellValue('G' . $cell_num, $questionData['info']);
                    
                    if (isset($questionData['options']))
                    {
                        $data['options'] = [];
                        
                        foreach ($questionData['options'] as $key => $option)
                        {
                            if ($questionData['type'] == 'single-choice')
                            {
                                $option_count++;
                                $option_name = htmlspecialchars_decode($option['name']);
                                $cell_num = $row_count + $option_count;
                                if (!isset($data['range_start'])) {
                                    $data['range_start'] = $cell_num;
                                }
                            
                                // Copy dropdown values to other cells and use that range to fix problem with commas in option name
                                $sheet->setCellValue('Y' . $cell_num, $option_name);
                                $sheet->setCellValue('Z' . $cell_num, $questionId);

                                array_push($data['options'], $option_name);
                                
                                if ($with_answers &&
                                    in_array($key, $survey_indicator_options))
                                {
                                    $sheet->setCellValue('C' . $row_count + 1, $option_name);
                                }
                            }
                            elseif ($questionData['type'] == 'multiple-choice')
                            {
                                $cell_num = $row_count + $key;
                                $data['cell'] = 'C' . $cell_num;
                                $data['options'] = ['Yes'];

                                self::setCellDropDown($sheet, $data);
                                self::setCellProtection($sheet, $data);
                                
                                if ($with_answers &&
                                    in_array($key, $survey_indicator_options))
                                {
                                    $sheet->setCellValue($data['cell'], 'Yes');
                                }
                            }
                        }
                    }

                    if ($questionData['type'] == 'single-choice' ||
                        $questionData['type'] == 'free-text')
                    {
                        $data['cell'] = 'C' . ++$row_count;
                        
                        if ($questionData['type'] == 'single-choice')
                        {
                            $data['options_with_comma'] = true;

                            self::setCellDropDown($sheet, $data);
                        }
                        elseif ($questionData['type'] == 'free-text')
                        {
                            if ($with_answers &&
                                !is_null($survey_indicator_answer))
                            {
                                $sheet->setCellValue($data['cell'], htmlspecialchars_decode($survey_indicator_answer->free_text));
                            }
                        }
                        self::setCellProtection($sheet, $data);
                    }
                    elseif ($questionData['type'] == 'multiple-choice') {
                        $row_count += count($questionData['options']);
                    }

                    // Reference Year
                    $years = range(2000, date('Y') + 1);
                    rsort($years);
                    $cell_num = $row_count + 2;
                    $data['cell'] = 'B' . $cell_num;
                    $data['options'] = $years;
                    $data['options_with_comma'] = false;
                    
                    self::setCellDropDown($sheet, $data);
                    self::setCellProtection($sheet, $data);
                    if ($with_answers &&
                        !is_null($survey_indicator_answer))
                    {
                        $sheet->setCellValue($data['cell'], $survey_indicator_answer->reference_year);
                    }

                    $row_count += 2;

                    // Reference Source
                    $cell_num = $row_count + 2;
                    $data['cell'] = 'B' . $cell_num;
                    
                    self::setCellProtection($sheet, $data);
                    $sheet->getRowDimension($cell_num)->setRowHeight(30, 'pt');
                    if ($with_answers &&
                        !is_null($survey_indicator_answer))
                    {
                        $sheet->setCellValue($data['cell'], htmlspecialchars_decode($survey_indicator_answer->reference_source));
                    }
                    
                    $row_count += 4;

                    $accordion = $parts[1];
                }

                // Rating
                $cell_num = $row_count;
                $data['cell'] = 'B' . $cell_num;
                $data['options'] = ['1', '2', '3', '4', '5'];
                $data['options_with_comma'] = false;

                self::setCellDropDown($sheet, $data);
                self::setCellProtection($sheet, $data);
                if ($with_answers) {
                    $sheet->setCellValue($data['cell'], ($survey_indicator->rating ? $survey_indicator->rating : ''));
                }

                // Comments
                $cell_num += 2;
                $data['cell'] = 'B' . $cell_num;

                self::setCellProtection($sheet, $data);
                $sheet->getRowDimension($cell_num)->setRowHeight(100, 'pt');
                if ($with_answers) {
                    $sheet->setCellValue($data['cell'], htmlspecialchars_decode($survey_indicator->comments));
                }

                // Identifier
                $cell_num += 2;
                $sheet->setCellValue('A' . $cell_num, 'Number ' . $identifier);
            }
        ];
    }

    private static function setCellDropDown($sheet, $data)
    {
        $validation = $sheet->getCell($data['cell'])->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        if ($data['options_with_comma']) {
            $validation->setFormula1('=Y' . $data['range_start'] . ':Y' . ($data['range_start'] + count($data['options']) - 1));
        }
        else {
            $validation->setFormula1(sprintf('"%s"', implode(',', $data['options'])));
        }
    }

    private static function setCellProtection($sheet, $data)
    {
        $protection = $sheet->getStyle($data['cell'])->getProtection();

        if ($data['protection']) {
            $protection->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_PROTECTED);
        }
        else {
            $protection->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
        }
    }
}
