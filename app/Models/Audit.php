<?php

namespace App\Models;

use App\HelperFunctions\GeneralHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;

class Audit extends Model
{
    use HasFactory;

    public static function getAuditByField($field)
    {
        switch ($field)
        {
            case 'auditable_type':
                return Audit::select(DB::raw("REPLACE(auditable_type, 'App\\\Models\\\', '') AS model"))->pluck('model')->unique()->toArray();
            default:
                return Audit::pluck($field)->unique()->toArray();
        }
    }

    public static function getAudit($id)
    {
        // Don't use Audit::find($id), as datatable requires collection and not model
        return Audit::where('id', $id)->get();
    }

    public static function getFilteredAudit($inputs)
    {
        $minDate = $inputs['minDate'];
        $maxDate = $inputs['maxDate'];
        $model = $inputs['model'];
        $event = $inputs['event'];
        $search = $inputs['search']['value'];

        return Audit::select(
            'audits.id',
            'audits.ip_address',
            'audits.event',
            'audits.description',
            'audits.auditable_name',
            'audits.new_values',
            'users.name AS user',
            DB::raw('DATE_FORMAT(audits.created_at, "%Y-%m-%d %T") AS date'),
            DB::raw("REPLACE(audits.auditable_type, 'App\\\Models\\\', '') AS model")
            )
            ->leftJoin('users', 'users.id', '=', 'audits.user_id')
            ->when($search, function ($q1) use ($search) {
                $q1->where(function($q2) use ($search) {
                    $q2->where('users.name', 'like', '%' . $search . '%')
                       ->orWhere('audits.ip_address', 'like', '%' . $search . '%')
                       ->orWhere('audits.auditable_type', 'like', '%' . $search . '%')
                       ->orWhere('audits.event', 'like', '%' . $search . '%')
                       ->orWhere('audits.auditable_name', 'like', '%' . $search . '%')
                       // Exclude json_data from search as json_data is not displayed in the view
                       ->orWhere(DB::raw("REGEXP_REPLACE(audits.new_values, '\"json_data.*\\\}\",', '')"), 'like', '%' . $search . '%');
                });
            })
            ->when($model != 'All', function ($query) use ($model) {
                $query->where('audits.auditable_type', 'like', '%' . $model);
            })
            ->when($event != 'All', function ($query) use ($event) {
                $query->where('audits.event', $event);
            })
            ->whereDate('audits.created_at', '>=', GeneralHelper::dateFormat($minDate))
            ->whereDate('audits.created_at', '<=', GeneralHelper::dateFormat($maxDate))
            ->get()
            ->toArray();
    }

    public static function getTotalAudit()
    {
        return Audit::get();
    }

    /**
    * Function sets custom audit event.
    *
    * @var model {
    * model: it must be a model instance so it doesn't duplicate the audit event like Model::find()
    * }
    * @var obj {
    * event: event in audits table e.g. updated -> Updated (in the view)
    * audit: new_values in audits table e.g. ['logged_out_at' => Carbon::now()] -> Logged Out At: ... (in the view)
    * }
    *
    */
    public static function setCustomAuditEvent($model, $obj)
    {
        $model->auditEvent = $obj['event'];
        $model->isCustomEvent = true;
        if (isset($obj['audit'])) {
            $model->auditCustomNew = $obj['audit'];
        }

        Event::dispatch(AuditCustom::class, [$model]);
    }
}
