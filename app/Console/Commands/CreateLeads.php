<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\VisaEnquiry;
use App\Support\Activity;
use App\Support\Leads;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gives website requests made before the lead pipeline existed a lead each, in
 * the stage their booking has reached, dated when the request came in. Safe to run
 * again: requests that already have a lead are skipped.
 */
class CreateLeads extends Command
{
    protected $signature = 'crm:create-leads {--dry-run : Show what would happen without saving anything}';

    protected $description = 'Create leads for existing bookings, requests and visa enquiries';

    /** Booking status => stage key. */
    private const STAGES = ['new' => 'new', 'contacted' => 'contacted', 'payment_pending' => 'awaiting_deposit', 'confirmed' => 'confirmed', 'completed' => 'confirmed', 'cancelled' => 'lost'];

    public function handle(): int
    {
        $created = 0;
        DB::beginTransaction();
        try {
            $taken = Lead::withTrashed()->whereNotNull('request_booking_id')->pluck('request_booking_id');
            foreach (Booking::whereNotIn('id', $taken)->oldest('id')->get() as $booking) {
                $lead = Leads::fromWebsiteBooking($booking) ?? throw new \RuntimeException("Lead for booking {$booking->reference} failed; see the log.");
                $stage = LeadStage::byKey(self::STAGES[$booking->status] ?? 'new');
                $lead->forceFill(['lead_stage_id' => $stage->id, 'lost_reason' => $stage->kind === 'lost' ? 'Booking cancelled' : null, 'created_at' => $booking->created_at])->saveQuietly();
                $this->line("Booking {$booking->reference} → lead {$lead->reference} ({$stage->name})");
                $created++;
            }
            $takenVisa = Lead::withTrashed()->whereNotNull('visa_enquiry_id')->pluck('visa_enquiry_id');
            foreach (VisaEnquiry::whereNotIn('id', $takenVisa)->oldest('id')->get() as $enquiry) {
                $lead = Leads::fromVisaEnquiry($enquiry) ?? throw new \RuntimeException("Lead for visa enquiry {$enquiry->reference} failed; see the log.");
                $lead->forceFill(['created_at' => $enquiry->created_at])->saveQuietly();
                $this->line("Visa enquiry {$enquiry->reference} → lead {$lead->reference} (New)");
                $created++;
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn("Dry run: {$created} leads would be created. Nothing was saved.");

                return self::SUCCESS;
            }
            Activity::record('leads_created', null, ['leads created' => $created], label: 'Existing requests');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $this->info("{$created} leads created.");

        return self::SUCCESS;
    }
}
