<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Index extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];
    protected $casts = [
        'json_data' => 'array',
        'report_json' => 'array'
    ];

    public function status()
    {
        return $this->belongsTo(IndexStatus::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function configuration()
    {
        return $this->belongsTo(IndexConfiguration::class, 'index_configuration_id');
    }
}
