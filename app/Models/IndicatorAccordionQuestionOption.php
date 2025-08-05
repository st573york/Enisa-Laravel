<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class IndicatorAccordionQuestionOption extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    public function question()
    {
        return $this->belongsTo(IndicatorAccordionQuestion::class);
    }

    public static function getIndicatorAccordionQuestionOptions($question)
    {
        return self::where('question_id', $question->id)->get();
    }

    public static function updateOrCreateIndicatorAccordionQuestionOption($data)
    {
        self::updateOrCreate(
            [
                'question_id' => $data['question_id'],
                'value' => $data['value']
            ],
            $data
        );
    }
}
