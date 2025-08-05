<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Area extends Model implements Auditable
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
                $data['description'] = 'The user created a new area';

                break;
            case 'updated':
                $data['description'] = 'The user updated an existing area';

                break;
            case 'deleted':
                $data['description'] = 'The user deleted an area';

                break;
            default:
                break;
        }
        
        $area = Area::find($data['auditable_id']);
        // Created / Updated
        if ($area)
        {
            $data['auditable_name'] = $area->name;
            $data['new_values']['year'] = $area->year;
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

        unset($data['new_values']['id'],
              $data['new_values']['default'],
              $data['new_values']['default_input_weight'],
              $data['new_values']['identifier']);

        return $data;
    }

    public function default_subarea()
    {
        return $this->hasMany(Subarea::class, 'default_area_id');
    }

    public static function getArea($id)
    {
        return self::with('default_subarea')->find($id);
    }

    public static function getAreas($year)
    {
        return self::with('default_subarea')->where('year', $year)->orderBy('identifier')->get();
    }
}
