<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class QuestionnaireUser extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        $user_id = QuestionnaireUser::find($this->getAttribute('id'))->user_id;
        $data['auditable_name'] = User::find($user_id)->name;

        unset($data['new_values']['user_id']);

        return $data;
    }

    public function questionnaire_country()
    {
        return $this->belongsTo(QuestionnaireCountry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
