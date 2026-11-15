<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\CustomerImport as ImportRecord;
use App\Models\CustomerTag;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Customer import from CSV or Excel (§4, §25): read the file, map its columns,
 * check every row (new / duplicate / error) without saving, then commit in one
 * transaction. Duplicates use the same rule as website bookings (Customers::find)
 * plus repeats inside the file itself.
 */
class CustomerImport
{
    public const MAX_ROWS = 5000;

    /** field => [label, header names that suggest it] */
    public const FIELDS = [
        'first_name' => ['First name', ['first name', 'firstname', 'first', 'given name', 'name first']],
        'last_name' => ['Last name', ['last name', 'lastname', 'last', 'surname', 'family name']],
        'full_name' => ['Full name (split into first / last)', ['name', 'full name', 'fullname', 'customer', 'customer name', 'contact', 'contact name', 'guest']],
        'preferred_name' => ['Preferred name', ['preferred name', 'nickname']],
        'email' => ['Email', ['email', 'e-mail', 'email address', 'mail']],
        'phone' => ['Phone', ['phone', 'phone number', 'mobile', 'cell', 'telephone', 'tel']],
        'whatsapp' => ['WhatsApp', ['whatsapp', 'whats app', 'wa']],
        'date_of_birth' => ['Date of birth', ['date of birth', 'dob', 'birthday', 'birth date', 'birthdate']],
        'city' => ['City', ['city', 'town']],
        'language' => ['Language', ['language', 'lang']],
        'notes' => ['Notes', ['notes', 'note', 'comments', 'comment', 'remarks']],
        'tags' => ['Tags (comma-separated)', ['tags', 'tag', 'labels', 'groups']],
        'marketing_opt_in' => ['Marketing consent (yes / no)', ['marketing', 'newsletter', 'opt in', 'optin', 'consent', 'subscribed', 'promotions']],
    ];

    /** Every row of the first sheet as strings (header first), up to MAX_ROWS + 1. */
    public static function rows(string $path): array
    {
        $absolute = Storage::disk('local')->path($path);
        $reader = str_ends_with(strtolower($path), '.xlsx') ? new XlsxReader() : new CsvReader(self::csvOptions($absolute));
        $reader->open($absolute);
        $rows = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = array_map(fn ($v) => $v instanceof DateTimeInterface ? $v->format('Y-m-d') : trim((string) (is_bool($v) ? ($v ? 'yes' : 'no') : $v)), $row->toArray());
                    if (array_filter($values, fn ($v) => $v !== '')) {
                        $rows[] = $values;
                    }
                    if (count($rows) > self::MAX_ROWS + 1) {
                        break 2;
                    }
                }
                break; // first sheet only
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    /** Excel saves CSV with ";" in some locales and in Windows-1252 rather than UTF-8. */
    private static function csvOptions(string $path): CsvOptions
    {
        $sample = (string) file_get_contents($path, false, null, 0, 65536);
        $firstLine = strtok($sample, "\n") ?: '';
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn ($d) => substr_count($firstLine, $d))->first();

        return new CsvOptions(FIELD_DELIMITER: $delimiter, ENCODING: mb_check_encoding($sample, 'UTF-8') ? 'UTF-8' : 'Windows-1252');
    }

    /** Column index => field, guessed from the header names. */
    public static function guessMapping(array $headers): array
    {
        $normal = fn (string $text) => trim(preg_replace('/[^a-z]+/', ' ', mb_strtolower($text))); // "E-mail" = "e mail" = "Email"
        $mapping = [];
        foreach ($headers as $i => $header) {
            $name = $normal($header);
            foreach (self::FIELDS as $field => [, $names]) {
                if (in_array($name, array_map($normal, $names), true) && !in_array($field, $mapping, true)) {
                    $mapping[$i] = $field;
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Check every data row without saving. Each result: line, contact, tags, consent,
     * outcome (new | duplicate | error), messages, match (existing customer).
     */
    public static function analyse(ImportRecord $import): array
    {
        $rows = self::rows($import->path);
        array_shift($rows);
        $mapping = $import->mapping ?? [];
        $seenEmails = $seenPhones = [];
        $results = [];

        foreach (array_slice($rows, 0, self::MAX_ROWS) as $n => $row) {
            $value = fn (string $field) => ($i = array_search($field, $mapping, true)) !== false ? trim((string) ($row[$i] ?? '')) : '';
            $contact = ['first_name' => $value('first_name'), 'last_name' => $value('last_name')];
            if ($contact['first_name'] === '' && ($full = $value('full_name')) !== '') {
                [$contact['first_name'], $contact['last_name']] = array_pad(preg_split('/\s+/', $full, 2), 2, $contact['last_name']);
            }
            foreach (['preferred_name', 'email', 'phone', 'whatsapp', 'city', 'language', 'notes'] as $field) {
                $contact[$field] = $value($field);
            }
            $contact['email'] = mb_strtolower($contact['email']);
            $messages = [];

            if ($contact['first_name'] === '') {
                $messages[] = 'No name.';
            }
            if ($contact['email'] !== '' && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
                $messages[] = "“{$contact['email']}” is not a valid email.";
            }
            if ($contact['email'] === '' && !Customer::phoneDigits($contact['phone'] ?: $contact['whatsapp'])) {
                $messages[] = 'Needs an email or a phone number.';
            }
            $contact['date_of_birth'] = null;
            if (($dob = $value('date_of_birth')) !== '') {
                [$contact['date_of_birth'], $error] = self::date($dob);
                if ($error) {
                    $messages[] = $error;
                }
            }
            $consent = self::yes($value('marketing_opt_in'));

            $outcome = $messages ? 'error' : 'new';
            $match = null;
            if (!$messages) {
                $digits = Customer::phoneDigits($contact['phone'] ?: $contact['whatsapp']);
                if (($contact['email'] !== '' && isset($seenEmails[$contact['email']])) || ($contact['email'] === '' && $digits && isset($seenPhones[$digits]))) {
                    $outcome = 'duplicate';
                    $messages[] = 'Repeats line '.($seenEmails[$contact['email']] ?? $seenPhones[$digits]).' of this file.';
                } elseif ($match = Customers::find($contact['email'], $contact['phone'], $contact['whatsapp'])) {
                    $outcome = 'duplicate';
                    $messages[] = "Already a customer: {$match->full_name} (#{$match->id}).";
                }
                if ($contact['email'] !== '') {
                    $seenEmails[$contact['email']] ??= $n + 2;
                }
                if ($digits) {
                    $seenPhones[$digits] ??= $n + 2;
                }
            }

            $results[] = [
                'line' => $n + 2, // the header is line 1
                'contact' => $contact,
                'tags' => array_values(array_filter(array_map('trim', explode(',', $value('tags'))))),
                'consent' => $consent,
                'outcome' => $outcome,
                'messages' => $messages,
                'match' => $match?->id,
                'in_file' => $outcome === 'duplicate' && !$match,
            ];
        }

        return $results;
    }

    /**
     * Save the checked rows: new rows become customers; existing customers get their
     * empty fields filled (only when options.duplicates = update); errors and repeats
     * inside the file are skipped. Returns the summary.
     */
    public static function commit(ImportRecord $import, string $actor): array
    {
        $options = $import->options ?? [];
        $results = self::analyse($import);
        $summary = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $consentSource = "Import {$import->filename} by {$actor}";
        $at = now();

        DB::transaction(function () use ($results, $options, $consentSource, $at, &$summary) {
            $extraTag = !empty($options['tag_id']) ? CustomerTag::find($options['tag_id']) : null;
            foreach ($results as $row) {
                if ($row['outcome'] === 'error') {
                    $summary['failed']++;
                    continue;
                }
                if ($row['in_file'] || ($row['outcome'] === 'duplicate' && ($options['duplicates'] ?? 'skip') !== 'update')) {
                    $summary['skipped']++;
                    continue;
                }
                $customer = $row['match'] ? Customer::find($row['match']) : new Customer(['source' => 'import', 'customer_status_id' => $options['status_id'] ?? null, 'last_activity_at' => $at]);
                foreach ($row['contact'] as $field => $value) {
                    if (filled($value) && blank($customer->{$field})) {
                        $customer->{$field} = $value;
                    }
                }
                if ($row['consent'] && !$customer->marketing_opt_in) {
                    $customer->forceFill(['marketing_opt_in' => true, 'marketing_opt_in_at' => $at, 'marketing_opt_in_source' => $consentSource]);
                }
                $isNew = !$customer->exists;
                $customer->save();

                $tagIds = collect($row['tags'])->map(fn ($name) => CustomerTag::firstOrCreate(['name' => mb_substr($name, 0, 60)], ['color' => 'info'])->id);
                if ($extraTag) {
                    $tagIds->push($extraTag->id);
                }
                $customer->tags()->syncWithoutDetaching($tagIds->unique()->all());
                $summary[$isNew ? 'created' : 'updated']++;
            }
        });

        return $summary;
    }

    /** [Y-m-d, error]. ISO dates, Excel dates, written months, and m/d/Y or d/m/Y when unambiguous. */
    private static function date(string $value): array
    {
        // Excel stores dates as days since 1899-12-30; unformatted cells arrive as that number (27700 = 1975-11-02).
        if (preg_match('/^\d{4,5}(\.0+)?$/', $value) && (int) $value > 366 && (int) $value < 60000) {
            return [Carbon::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d'), null];
        }
        if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $value, $m)) {
            [$a, $b, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if ($a <= 12 && $b <= 12 && $a !== $b) {
                return [null, "Date of birth “{$value}” is ambiguous (month/day or day/month?). Use YYYY-MM-DD."];
            }
            [$month, $day] = $a > 12 ? [$b, $a] : [$a, $b];

            return checkdate($month, $day, $year) && $year > 1900 ? [sprintf('%04d-%02d-%02d', $year, $month, $day), null] : [null, "Date of birth “{$value}” is not a real date."];
        }
        try {
            $date = Carbon::parse($value);

            return $date->isPast() && $date->year > 1900 ? [$date->format('Y-m-d'), null] : [null, "Date of birth “{$value}” is not a valid past date."];
        } catch (\Throwable) {
            return [null, "Date of birth “{$value}” is not a date."];
        }
    }

    private static function yes(string $value): bool
    {
        return in_array(mb_strtolower(trim($value)), ['yes', 'y', 'true', '1', 'x', 'opted in', 'subscribed'], true);
    }
}
