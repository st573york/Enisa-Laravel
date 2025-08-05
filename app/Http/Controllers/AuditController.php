<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuditController extends Controller
{
    public function view()
    {
        $models = Audit::getAuditByField('auditable_type');
        $events = Audit::getAuditByField('event');
        
        return view('components.audit', ['dateToday' => Carbon::today()->format('d-m-Y'), 'models' => $models, 'events' => $events]);
    }

    public function list(Request $request)
    {
        $inputs = $request->all();
        $totalAudit = Audit::getTotalAudit();
        $filteredAudit = Audit::getFilteredAudit($inputs);

        // Perform offset, limit and order by to avoid running the same query 3 times (recordsTotal, recordsFiltered, data)
        $column = $inputs['order'][0]['column'];
        $order = $inputs['columns'][$column]['data'];
        $sort = ($inputs['order'][0]['dir'] == 'asc') ? SORT_ASC : SORT_DESC;
        $offset = $inputs['start'];
        $length = $inputs['length'];

        $keys = array_column($filteredAudit, $order);
        array_multisort($keys, $sort, $filteredAudit);

        $data = array_slice($filteredAudit, $offset, $length);

        return response()->json([
            'draw' => (int)$inputs['draw'],
            'recordsTotal' => count($totalAudit),
            'recordsFiltered' => count($filteredAudit),
            'data' => $data], 200);
    }

    public function showChanges()
    {
        return view('ajax.audit-changes');
    }

    public function listChanges(Audit $audit)
    {
        $data = Audit::getAudit($audit->id);
        
        return response()->json(['data' => $data], 200);
    }
}
