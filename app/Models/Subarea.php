<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Subarea extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        switch ($data['event'])
        {
            case 'created':
                $data['description'] = 'The user created a new subarea';

                break;
            case 'updated':
                $data['description'] = 'The user updated an existing subarea';

                break;
            case 'deleted':
                $data['description'] = 'The user deleted a subarea';

                break;
            default:
                break;
        }
        
        $subarea = Subarea::find($data['auditable_id']);
        // Created / Updated
        if ($subarea)
        {
            $data['auditable_name'] = $subarea->name;
            $data['new_values']['year'] = $subarea->year;
        }
        // Deleted
        else
        {
            if (Arr::has($data, 'old_values.name')) {
                $data['auditable_name'] = $data['old_values']['name'];
            }
            if (Arr::has($data, 'old_values.year')) {
                $data['new_values']['year'] = $data['old_values']['year'];
            }
        }

        if (Arr::has($data, 'new_values.default_area_id')) {
            $data['new_values']['area'] = Area::find($data['new_values']['default_area_id'])->name;
        }

        unset($data['new_values']['id'],
              $data['new_values']['short_name'],
              $data['new_values']['default_area_id'],
              $data['new_values']['default_input_weight'],
              $data['new_values']['identifier']);

        return $data;
    }

    public function default_area()
    {
        return $this->belongsTo(Area::class);
    }

    public function default_indicator()
    {
        return $this->hasMany(Indicator::class, 'default_subarea_id');
    }

    public static function getSubarea($id)
    {
        return self::with('default_indicator')->find($id);
    }

    public static function getSubareas($year)
    {
        return self::with(['default_area', 'default_indicator'])->where('year', $year)->orderBy('identifier')->get();
    }
}
