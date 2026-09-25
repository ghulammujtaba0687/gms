<?php

namespace App\Services;

use App\Services\AuditLogService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function exportCsv(string $reportTitle, array $headers, array $rows): StreamedResponse
    {
        AuditLogService::log(
            'Reports & Analytics',
            'Exported CSV Report: '.$reportTitle,
            null,
            ['headers' => $headers, 'row_count' => count($rows)]
        );

        $filename = strtolower(str_replace(' ', '_', $reportTitle)).'_'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, $headers);

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
}
