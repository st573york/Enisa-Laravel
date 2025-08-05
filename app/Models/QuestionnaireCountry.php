<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class QuestionnaireCountry extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $casts = [
        'json_data' => 'array'
    ];
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        $country_id = QuestionnaireCountry::find($this->getAttribute('id'))->country_id;
        $data['auditable_name'] = Country::find($country_id)->name;

        $questionnaire_id = QuestionnaireCountry::find($this->getAttribute('id'))->questionnaire_id;
        $data['new_values']['questionnaire'] = Questionnaire::find($questionnaire_id)->title;

        unset($data['new_values']['questionnaire_id'],
              $data['new_values']['country_id'],
              $data['new_values']['submitted_by'],
              $data['new_values']['approved_by']);

        return $data;
    }

    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function submitted_by()
    {
        return $this->belongsTo(User::class, 'submitted_by', 'id');
    }

    public function user_questionnaires()
    {
        return $this->hasMany(QuestionnaireUser::class);
    }

    public static function saveQuestionnaireCountry($questionnaire)
    {
        self::disableAuditing();
        $questionnaire->save();
        self::enableAuditing();
    }
}
