<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** A list as CSV or Excel (CRM §25), streamed row by row so large exports stay light. */
class TableExport
{
    public const FORMATS = ['csv', 'xlsx'];

    /** @param array<string, callable> $columns header => fn ($row) => value */
    public static function download(string $name, iterable $rows, array $columns, string $format): StreamedResponse
    {
        $header = array_keys($columns);
        $line = fn ($row) => array_map(fn ($c) => (string) ($c($row) ?? ''), array_values($columns));

        return $format === 'xlsx'
            ? response()->streamDownload(function () use ($rows, $header, $line) {
                $writer = new Writer();
                $writer->openToFile('php://output');
                $writer->addRow(Row::fromValues($header));
                foreach ($rows as $row) {
                    $writer->addRow(Row::fromValues($line($row)));
                }
                $writer->close();
            }, "$name.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            : response()->streamDownload(function () use ($rows, $header, $line) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // so Excel reads accented names correctly
                fputcsv($out, $header);
                foreach ($rows as $row) {
                    fputcsv($out, $line($row));
                }
                fclose($out);
            }, "$name.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
