<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Questionnaire extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        // Item has been renamed or deleted?
        if (Arr::has($data, 'old_values.title')) {
            $data['auditable_name'] = $data['old_values']['title'];
        }
        // Item has been created?
        else
        {
            $questionnaire = Questionnaire::find($this->getAttribute('id'));
            if ($questionnaire) {
                $data['auditable_name'] = $questionnaire->title;
            }
        }

        if (Arr::has($data, 'new_values.index_configuration_id')) {
            $data['new_values']['index'] = IndexConfiguration::find($data['new_values']['index_configuration_id'])->name;
        }

        if (Arr::has($data, 'new_values.published') &&
            $data['new_values']['published'])
        {
            $data['new_values']['status'] = 'Published';
        }
        
        unset($data['new_values']['index_configuration_id'],
              $data['new_values']['user_id'],
              $data['new_values']['published']);

        return $data;
    }

    public function configuration()
    {
        return $this->belongsTo(IndexConfiguration::class, 'index_configuration_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country_questionnaires()
    {
        return $this->hasMany(QuestionnaireCountry::class);
    }

    public function indicators()
    {
        return $this->hasMany(QuestionnaireIndicator::class);
    }

    public static function getLastPublishedQuestionnaire()
    {
        return self::where('published', true)->orderBy('year', 'desc')->skip(1)->first();
    }

    public static function getLatestPublishedQuestionnaire()
    {
        return self::where('published', true)->orderBy('year', 'desc')->first();
    }

    public static function getExistingPublishedQuestionnaireForYear($year)
    {
        return self::where(
            [
                ['published', true],
                ['year', $year]
            ]
        )->orderBy('year', 'desc')->first();
    }

    public static function getPublishedQuestionnaires($index = null)
    {
        return self::where('published', true)
            ->when(!is_null($index), function ($query) use ($index) {
                return $query->where('year', $index->year);
            })
            ->orderBy('year', 'desc')
            ->get();
    }
}
