<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class IndicatorValue extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public static function updateOrCreateIndicatorValue($data)
    {
        IndicatorValue::updateOrCreate(
            [
                'indicator_id' => $data['indicator_id'],
                'country_id' => $data['country_id'],
                'year' => $data['year']
            ],
            $data
        );
    }
}
