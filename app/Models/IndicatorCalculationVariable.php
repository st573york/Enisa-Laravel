<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorCalculationVariable extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }
}
