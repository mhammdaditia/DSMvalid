<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function dsmPage()
    {
        $basePath = '/DCAM/report_dsm';

        // URL tampilan HTML (ReportViewer)
        $htmlUrl = 'http://rantsqco401/ReportServer/Pages/ReportViewer.aspx'
            .'?'.rawurlencode($basePath)
            .'&'.http_build_query([
                'rs:Command' => 'Render',
                'rs:Format'  => 'HTML4.0',
            ]);

        // URL export PDF
        $pdfUrl = 'http://rantsqco401/ReportServer/Pages/ReportViewer.aspx'
            .'?'.rawurlencode($basePath)
            .'&'.http_build_query([
                'rs:Command' => 'Render',
                'rs:Format'  => 'PDF',
            ]);

        return Inertia::render('Reports/Dsm', [
            'htmlUrl' => $htmlUrl,
            'pdfUrl'  => $pdfUrl,
        ]);
    }
}