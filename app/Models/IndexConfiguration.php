<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class IndexConfiguration extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $casts = [
        'json_data' => 'array'
    ];
    protected $guarded = [];

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
            $indexConfiguration = self::find($this->getAttribute('id'));
            if ($indexConfiguration) {
                $data['auditable_name'] = $indexConfiguration->name;
            }
        }

        if (Arr::has($data, 'old_values.draft') &&
            Arr::has($data, 'new_values.draft') &&
            boolval($data['old_values']['draft']) != $data['new_values']['draft'])
        {
            $data['new_values']['status'] = ($data['new_values']['draft']) ? 'Draft' : 'Published';
        }

        if (Arr::has($data, 'old_values.eu_published') &&
            Arr::has($data, 'new_values.eu_published') &&
            boolval($data['old_values']['eu_published']) != $data['new_values']['eu_published'])
        {
            $data['new_values']['eu_reports_visualisations'] = ($data['new_values']['eu_published']) ? 'Published' : 'Unpublished';
        }

        if (Arr::has($data, 'old_values.ms_published') &&
            Arr::has($data, 'new_values.ms_published') &&
            boolval($data['old_values']['ms_published']) != $data['new_values']['ms_published'])
        {
            $data['new_values']['ms_reports_visualisations'] = ($data['new_values']['ms_published']) ? 'Published' : 'Unpublished';
        }

        unset(
            $data['new_values']['user_id'],
            $data['new_values']['draft'],
            $data['new_values']['eu_published'],
            $data['new_values']['ms_published']
        );

        return $data;
    }

    public function index()
    {
        return $this->hasMany(Index::class);
    }

    public function baseline()
    {
        return $this->hasOne(BaselineIndex::class);
    }

    public static function getLoadedPublishedConfiguration($latest_index_data = null)
    {
        $loaded_index_data = self::getExistingPublishedConfigurationForYear($_COOKIE['index-year']);
        if (is_null($loaded_index_data)) {
            $loaded_index_data = (is_null($latest_index_data)) ? self::getLatestPublishedConfiguration() : $latest_index_data;
        }

        return $loaded_index_data;
    }

    public static function getLatestPublishedConfiguration()
    {
        return self::where('draft', false)->orderBy('year', 'desc')->first();
    }

    public static function getPublishedConfigurations($index = null)
    {
        return self::where('draft', false)
            ->when(!is_null($index), function ($query) use ($index) {
                return $query->where('id', '!=', $index->id);
            })
            ->orderBy('year', 'desc')->get();
    }

    public static function getIndexConfigurations()
    {
        return self::select('index_configurations.*', 'users.name as user')
            ->join('users', 'users.id', '=', 'index_configurations.user_id')
            ->get();
    }

    public static function getIndexConfiguration($id)
    {
        return self::find($id);
    }

    public static function getExistingDraftConfigurationForYear($year)
    {
        return self::where(
            [
                ['draft', true],
                ['year', $year]
            ]
        )->orderBy('year', 'desc')->first();
    }

    public static function getExistingPublishedConfigurationForYear($year)
    {
        return self::where(
            [
                ['draft', false],
                ['year', $year]
            ]
        )->orderBy('year', 'desc')->first();
    }

    public static function generateIndexConfigurationTemplate($year)
    {
        $contents = [];
        $areas = Area::where('default', true)->where('year', $year)->get();

        foreach ($areas as $area) {
            array_push($contents, [
                'area' => [
                    'id' => $area->id,
                    'identifier' => $area->identifier,
                    'name' => $area->name,
                    'description' => $area->description,
                    'weight' => $area->default_weight,
                    'subareas' => self::getSubareas($area)
                ]
            ]);
        }

        return (!empty($contents)) ? ['contents' => $contents] : '{}';
    }

    private static function getSubareas($area)
    {
        $contents = [];
        $area_data = Area::with('default_subarea')->find($area->id);
        $subareas = $area_data->default_subarea;

        foreach ($subareas as $subarea) {
            array_push($contents, [
                'id' => $subarea->id,
                'identifier' => $subarea->identifier,
                'name' => $subarea->name,
                'description' => $subarea->description,
                'weight' => $subarea->default_weight,
                'short_name' => $subarea->short_name,
                'indicators' => self::getIndicators($subarea)
            ]);
        }

        return $contents;
    }

    private static function getIndicators($subarea)
    {
        $contents = [];
        $subarea_data = Subarea::with('default_indicator')->find($subarea->id);
        $indicators = $subarea_data->default_indicator;

        foreach ($indicators as $indicator) {
            array_push($contents, [
                'id' => $indicator->id,
                'identifier' => $indicator->identifier,
                'name' => $indicator->name,
                'short_name' => $indicator->short_name,
                'weight' => $indicator->default_weight,
                'algorithm' => $indicator->algorithm,
                'source' => $indicator->source,
                'report_year' => $indicator->report_year,
                'disclaimers' => $indicator->disclaimers
            ]);
        }

        return $contents;
    }

    public static function updateDraftIndexConfigurationJsonData($year)
    {
        self::disableAuditing();
        $draft_index = self::getExistingDraftConfigurationForYear($year);
        if (!is_null($draft_index))
        {
            $draft_index->json_data = self::generateIndexConfigurationTemplate($year);
            $draft_index->save();
        }
        self::enableAuditing();
    }
}
