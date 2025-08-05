<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Country extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**  
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'iso',
        'flag_src'
    ];

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        // Item has been renamed or deleted?
        if (Arr::has($data, 'old_values.name')) {
            $data['auditable_name'] = $data['old_values']['name'];
        }
        // Item has been created?
        else
        {
            $country = Country::find($this->getAttribute('id'));
            if ($country) {
                $data['auditable_name'] = $country->name;
            }
        }

        return $data;
    }

    public function indices()
    {
        return $this->hasMany(Index::class);
    }

    public function questionnaires()
    {
        return $this->hasMany(QuestionnaireUser::class);
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
