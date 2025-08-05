<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class BaselineIndex extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];
    protected $casts = [
        'json_data' => 'array',
        'report_json' => 'array'
    ];

    public function configuration()
    {
        return $this->belongsTo(IndexConfiguration::class, 'index_configuration_id');
    }
}
