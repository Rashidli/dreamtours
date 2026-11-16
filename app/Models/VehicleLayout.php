<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

/**
 * A reusable seating plan (CRM §9). layout = {rows, cols, cells: [[{t, l?}, …], …]} where t is
 * one of TYPES; only "seat" cells can hold passengers, each with a unique label ("1", "12A").
 */
class VehicleLayout extends Model
{
    use Concerns\RecordsActivity, SoftDeletes;

    /** cell type => [label, short mark shown in the cell] */
    public const TYPES = [
        'seat' => ['Passenger seat', ''],
        'leader' => ['Tour leader seat', 'L'],
        'driver' => ['Driver', 'D'],
        'door' => ['Door', '⇥'],
        'aisle' => ['Aisle', ''],
        'empty' => ['Empty space', ''],
    ];

    public const MAX_ROWS = 25;
    public const MAX_COLS = 7;

    protected $fillable = ['name', 'transportation_id', 'layout', 'seat_count', 'is_active'];

    protected $casts = ['layout' => 'array', 'is_active' => 'boolean'];

    public function transportation() { return $this->belongsTo(Transportation::class); }

    /**
     * A starting plan: a front row (driver left, leader seat, door right), $rows passenger rows of
     * $left + aisle + $right seats, and optionally a back bench across the aisle. Seats are numbered
     * row by row, left to right.
     */
    public static function generate(int $rows, int $left, int $right, bool $backBench = false): array
    {
        $cols = $left + 1 + $right;
        $front = array_fill(0, $cols, ['t' => 'empty']);
        $front[0] = ['t' => 'driver'];
        $front[$left] = ['t' => 'aisle'];
        $front[$left + 1] = ['t' => 'leader'];
        if ($right > 1) {
            $front[$cols - 1] = ['t' => 'door'];
        }
        $cells = [$front];
        for ($r = 0; $r < $rows; $r++) {
            $cells[] = array_map(fn ($c) => ['t' => $c === $left ? 'aisle' : 'seat'], range(0, $cols - 1));
        }
        if ($backBench) {
            $cells[] = array_fill(0, $cols, ['t' => 'seat']);
        }

        return self::renumber(['rows' => count($cells), 'cols' => $cols, 'cells' => $cells]);
    }

    /** Number every passenger seat 1, 2, 3… row by row, left to right. */
    public static function renumber(array $layout): array
    {
        $n = 0;
        foreach ($layout['cells'] as $r => $row) {
            foreach ($row as $c => $cell) {
                if ($cell['t'] === 'seat') {
                    $layout['cells'][$r][$c]['l'] = (string) ++$n;
                } else {
                    unset($layout['cells'][$r][$c]['l']);
                }
            }
        }

        return $layout;
    }

    public static function countSeats(array $layout): int
    {
        return count(self::seatLabels($layout));
    }

    /** @return string[] passenger seat labels in plan order */
    public static function seatLabels(array $layout): array
    {
        $labels = [];
        foreach ($layout['cells'] ?? [] as $row) {
            foreach ($row as $cell) {
                if (($cell['t'] ?? null) === 'seat') {
                    $labels[] = (string) $cell['l'];
                }
            }
        }

        return $labels;
    }

    /** Check a submitted plan and return it in canonical form, or fail with a message staff can act on. */
    public static function clean(mixed $layout): array
    {
        $fail = fn (string $m) => throw ValidationException::withMessages(['layout' => $m]);
        if (!is_array($layout) || !is_array($layout['cells'] ?? null)) {
            $fail('The seating plan is missing.');
        }
        $cells = array_values($layout['cells']);
        $rows = count($cells);
        $cols = count($cells[0] ?? []);
        if ($rows < 1 || $rows > self::MAX_ROWS || $cols < 1 || $cols > self::MAX_COLS) {
            $fail('A plan has 1–'.self::MAX_ROWS.' rows and 1–'.self::MAX_COLS.' columns.');
        }
        $seen = [];
        $clean = [];
        foreach ($cells as $r => $row) {
            if (!is_array($row) || count($row) !== $cols) {
                $fail('Every row must have the same number of columns.');
            }
            foreach (array_values($row) as $c => $cell) {
                $type = is_array($cell) ? ($cell['t'] ?? null) : null;
                if (!isset(self::TYPES[$type])) {
                    $fail('Unknown cell type in row '.($r + 1).'.');
                }
                $out = ['t' => $type];
                if ($type === 'seat') {
                    $label = trim((string) ($cell['l'] ?? ''));
                    if ($label === '' || mb_strlen($label) > 10 || !preg_match('/^[\pL\pN\-]+$/u', $label)) {
                        $fail('Seat labels are 1–10 letters or digits (row '.($r + 1).').');
                    }
                    if (isset($seen[mb_strtoupper($label)])) {
                        $fail("Seat “{$label}” appears twice. Every seat needs its own number.");
                    }
                    $seen[mb_strtoupper($label)] = true;
                    $out['l'] = $label;
                }
                $clean[$r][$c] = $out;
            }
        }
        if (!$seen) {
            $fail('Add at least one passenger seat.');
        }

        return ['rows' => $rows, 'cols' => $cols, 'cells' => $clean];
    }

    public function activityLabel(): string { return $this->name; }
}
