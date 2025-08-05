<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\IndexConfiguration;

class DocumentLibraryController extends Controller
{
    public function view()
    {
        return view('components.documents-library');
    }

    public function downloadDocumentLibrary($document)
    {
        Audit::setCustomAuditEvent(
            IndexConfiguration::getLatestPublishedConfiguration(),
            ['event' => 'exported', 'audit' => ['file' => $document]]
        );

        $current_year = date('Y');
        
        $inputFile = app_path() . '/files/' . $document;

        return response()->download($inputFile, $document, ['Content-Type' => 'application/force-download']);
    }
}
