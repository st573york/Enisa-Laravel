<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class IndicatorAccordionQuestion extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    public function accordion()
    {
        return $this->belongsTo(IndicatorAccordion::class);
    }

    public function type()
    {
        return $this->belongsTo(IndicatorQuestionType::class);
    }

    public function options()
    {
        return $this->hasMany(IndicatorAccordionQuestionOption::class, 'question_id');
    }

    public static function getIndicatorAccordionQuestion($accordion, $order)
    {
        return self::where('accordion_id', $accordion->id)
            ->where('order', $order)
            ->first();
    }

    public static function updateOrCreateIndicatorAccordionQuestion($data)
    {
        return self::updateOrCreate(
            [
                'accordion_id' => $data['accordion_id'],
                'order' => $data['order']
            ],
            $data
        );
    }
}
