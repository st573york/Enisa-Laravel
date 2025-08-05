<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class QuestionnaireIndicator extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    
    protected $guarded = [];

    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        $indicator_id = QuestionnaireIndicator::find($this->getAttribute('id'))->indicator_id;
        $data['auditable_name'] = Indicator::find($indicator_id)->short_name;

        $questionnaire_id = QuestionnaireIndicator::find($this->getAttribute('id'))->questionnaire_id;
        $data['new_values']['questionnaire'] = Questionnaire::find($questionnaire_id)->title;

        unset($data['new_values']['indicator_id'],
              $data['new_values']['questionnaire_id']);

        return $data;
    }

    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }


}
