<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class EurostatIndicatorVariable extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    public function indicator_eurostat()
    {
        return $this->belongsTo(EurostatIndicator::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
