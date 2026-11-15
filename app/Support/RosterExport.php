<?php

namespace App\Support;

use App\Models\TourDeparture;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;

/** Passenger list as CSV, Excel or PDF. Each download is written to the activity history. */
class RosterExport
{
    public const FORMATS = ['csv', 'xlsx', 'pdf'];

    /** @param array<string, callable> $columns */
    public static function download(TourDeparture $departure, Collection $rows, array $columns, string $format): Response
    {
        $name = 'passengers-'.str($departure->tour?->name)->slug()->limit(40, '').'-'.$departure->departs_at->format('Y-m-d');
        $table = $rows->values()->map(fn ($row, $i) => array_merge([$i + 1], array_map(fn ($c) => (string) ($c($row['passenger'], $row['booking']) ?? ''), array_values($columns))))->all();
        $header = array_merge(['#'], array_keys($columns));
        Activity::record('roster_exported', $departure, ['format' => [null, strtoupper($format)], 'rows' => [null, (string) count($table)], 'columns' => [null, implode(', ', array_keys($columns))]]);

        return match ($format) {
            'csv' => response()->streamDownload(function () use ($header, $table) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // Excel reads UTF-8 names correctly with a BOM
                fputcsv($out, $header);
                foreach ($table as $line) {
                    fputcsv($out, $line);
                }
                fclose($out);
            }, "$name.csv", ['Content-Type' => 'text/csv; charset=UTF-8']),
            'xlsx' => response()->streamDownload(function () use ($header, $table) {
                $writer = new Writer();
                $writer->openToFile('php://output');
                $writer->addRow(Row::fromValues($header));
                foreach ($table as $line) {
                    $writer->addRow(Row::fromValues($line));
                }
                $writer->close();
            }, "$name.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
            default => self::pdf($departure, $header, $table, $name),
        };
    }

    private static function pdf(TourDeparture $departure, array $header, array $table, string $name): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('admin.roster.pdf', ['departure' => $departure, 'header' => $header, 'table' => $table])->render());
        $pdf->setPaper('letter', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'.pdf"']);
    }
}
