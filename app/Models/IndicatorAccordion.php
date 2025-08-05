<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class IndicatorAccordion extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function questions()
    {
        return $this->hasMany(IndicatorAccordionQuestion::class, 'accordion_id');
    }

    public static function getIndicatorAccordion($indicator, $order)
    {
        return self::where('indicator_id', $indicator->id)
            ->where('order', $order)
            ->first();
    }

    public static function updateOrCreateIndicatorAccordion($data)
    {
        return self::updateOrCreate(
            [
                'indicator_id' => $data['indicator_id'],
                'order' => $data['order']
            ],
            $data
        );
    }
}
