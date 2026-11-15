<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\VisaEnquiry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Possible duplicate customers and their reviewed merge (§3, §4). Nothing is merged
 * automatically: staff compare two records, choose each field, and the second
 * customer's bookings, requests, tags and notes move to the first. The second
 * record is archived with merged_into_id, never erased.
 */
class CustomerMerge
{
    /** Fields staff choose between, with their labels. */
    public const FIELDS = [
        'first_name' => 'First name', 'last_name' => 'Last name', 'preferred_name' => 'Preferred name',
        'email' => 'Email', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'date_of_birth' => 'Date of birth',
        'city' => 'City', 'language' => 'Language', 'customer_status_id' => 'Status', 'assigned_user_id' => 'Assigned to', 'source' => 'Source',
    ];

    /**
     * Pairs that look like the same person: same phone, or same full name. Archived
     * customers and pairs marked "not a duplicate" are left out.
     *
     * @return Collection<int, array{0: Customer, 1: Customer, reasons: string[]}>
     */
    public static function suggestions(int $limit = 100): Collection
    {
        $dismissed = DB::table('customer_duplicate_dismissals')->get()->map(fn ($d) => "{$d->customer_id}-{$d->other_customer_id}")->flip();
        $pairs = [];
        $add = function (Collection $group, string $reason) use (&$pairs, $dismissed) {
            $ids = $group->pluck('id')->sort()->values();
            foreach ($ids as $i => $a) {
                foreach ($ids->slice($i + 1) as $b) {
                    if (!isset($dismissed["{$a}-{$b}"])) {
                        $pairs["{$a}-{$b}"][] = $reason;
                    }
                }
            }
        };

        $active = Customer::query()->select('id', 'phone_digits', 'first_name', 'last_name')->get();
        $active->whereNotNull('phone_digits')->groupBy('phone_digits')->filter(fn ($g) => $g->count() > 1)->each(fn ($g) => $add($g, 'Same phone'));
        $active->groupBy(fn ($c) => mb_strtolower(trim(preg_replace('/\s+/', ' ', "{$c->first_name} {$c->last_name}"))))
            ->filter(fn ($g, $name) => $g->count() > 1 && str_contains($name, ' '))->each(fn ($g) => $add($g, 'Same name'));

        $customers = Customer::with('status')->withCount('bookings')->findMany(collect(array_keys($pairs))->flatMap(fn ($key) => explode('-', $key))->unique())->keyBy('id');

        return collect($pairs)->take($limit)->map(function ($reasons, $key) use ($customers) {
            [$a, $b] = explode('-', $key);

            return [$customers[$a], $customers[$b], 'reasons' => array_values(array_unique($reasons))];
        })->values();
    }

    public static function dismiss(Customer $a, Customer $b, ?int $userId): void
    {
        [$low, $high] = $a->id < $b->id ? [$a, $b] : [$b, $a];
        DB::table('customer_duplicate_dismissals')->insertOrIgnore(['customer_id' => $low->id, 'other_customer_id' => $high->id, 'user_id' => $userId, 'created_at' => now()]);
        Activity::record('duplicate_dismissed', $low, ['not a duplicate of' => "{$high->full_name} (#{$high->id})"]);
    }

    /**
     * Merge $other into $keep. $choices: field => 'keep' | 'other' (missing = keep, unless keep is empty).
     * Returns what moved, for the confirmation message.
     */
    public static function merge(Customer $keep, Customer $other, array $choices): array
    {
        abort_if($keep->is($other) || $other->merged_into_id, 422, 'These customers cannot be merged.');

        return DB::transaction(function () use ($keep, $other, $choices) {
            $taken = [];
            $lost = [];
            foreach (array_keys(self::FIELDS) as $field) {
                // No choice sent (e.g. a field hidden from this user): fill a gap, never overwrite.
                $choice = $choices[$field] ?? (blank($keep->getAttribute($field)) && filled($other->getAttribute($field)) ? 'other' : 'keep');
                if ($choice === 'other' && $keep->getAttribute($field) != $other->getAttribute($field)) {
                    if (in_array($field, ['email', 'phone', 'whatsapp'], true) && filled($keep->getAttribute($field))) {
                        $lost[] = self::FIELDS[$field].': '.$keep->getAttribute($field);
                    }
                    $keep->setAttribute($field, $other->getAttribute($field));
                    $taken[] = self::FIELDS[$field];
                } elseif (in_array($field, ['email', 'phone', 'whatsapp'], true) && filled($other->getAttribute($field)) && $other->getAttribute($field) != $keep->getAttribute($field)) {
                    $lost[] = self::FIELDS[$field].': '.$other->getAttribute($field);
                }
            }

            // Nothing is lost: notes are joined, unused contact details are kept in the notes.
            $note = trim(implode("\n", array_filter([
                $other->notes,
                $lost ? 'Other contact details: '.implode('; ', $lost) : null,
            ])));
            if ($note !== '') {
                $keep->notes = trim(($keep->notes ? $keep->notes."\n\n" : '')."— Merged from {$other->full_name} (#{$other->id}) —\n".$note);
            }
            // Earliest consent record wins; consent is never switched off by a merge.
            if ($other->marketing_opt_in && (!$keep->marketing_opt_in || $other->marketing_opt_in_at?->lt($keep->marketing_opt_in_at))) {
                $keep->forceFill($other->only(['marketing_opt_in', 'marketing_opt_in_at', 'marketing_opt_in_source']));
            }
            $keep->last_activity_at = collect([$keep->last_activity_at, $other->last_activity_at])->filter()->max();
            $keep->created_at = collect([$keep->created_at, $other->created_at])->filter()->min(); // customer since first contact

            $keep->save();

            $bookings = Booking::where('customer_id', $other->id)->pluck('reference');
            $enquiries = VisaEnquiry::where('customer_id', $other->id)->pluck('reference');
            Booking::where('customer_id', $other->id)->update(['customer_id' => $keep->id]);
            VisaEnquiry::where('customer_id', $other->id)->update(['customer_id' => $keep->id]);
            $keep->tags()->syncWithoutDetaching($other->tags()->pluck('customer_tags.id'));

            // Archived with a pointer to the kept record; its own details stay as they were.
            $other->forceFill(['merged_into_id' => $keep->id])->saveQuietly();
            $other->delete();

            $moved = ['merged customer' => "{$other->full_name} (#{$other->id})", 'bookings moved' => $bookings->implode(', ') ?: 'none', 'visa enquiries moved' => $enquiries->implode(', ') ?: 'none', 'fields taken from merged customer' => implode(', ', $taken) ?: 'none'];
            Activity::record('customer_merged', $keep, $moved);
            Activity::record('merged_into', $other, ['kept customer' => "{$keep->full_name} (#{$keep->id})"]);

            return $moved;
        });
    }
}
