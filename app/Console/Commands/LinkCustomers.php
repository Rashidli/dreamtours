<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\VisaEnquiry;
use App\Support\Activity;
use App\Support\Customers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Links bookings and visa enquiries made before the customer directory existed
 * to customer records (same email, else same phone, else a new customer). Safe to
 * run again: records that already have a customer are left alone.
 */
class LinkCustomers extends Command
{
    protected $signature = 'crm:link-customers {--dry-run : Show what would happen without saving anything}';

    protected $description = 'Link existing bookings and visa enquiries to customer records';

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $before = Customer::withTrashed()->count();
        $linked = 0;

        DB::beginTransaction();
        try {
            // Oldest first, so a customer's first booking or request becomes their source and signup date.
            foreach (Booking::whereNull('customer_id')->oldest('id')->get() as $booking) {
                $customer = Customers::forBooking($booking);
                $booking->forceFill(['customer_id' => $customer->id])->saveQuietly();
                $this->line("Booking {$booking->reference} → {$customer->full_name} (#{$customer->id})");
                $linked++;
            }
            foreach (VisaEnquiry::whereNull('customer_id')->oldest('id')->get() as $enquiry) {
                $customer = Customers::forVisaEnquiry($enquiry);
                $enquiry->forceFill(['customer_id' => $customer->id])->saveQuietly();
                $this->line("Visa enquiry {$enquiry->reference} → {$customer->full_name} (#{$customer->id})");
                $linked++;
            }
            // Signup date = first contact, not today.
            foreach (Customer::where('created_at', '>=', now()->subMinute())->get() as $customer) {
                $first = collect([$customer->bookings()->min('created_at'), $customer->visaEnquiries()->min('created_at')])->filter()->min();
                if ($first) {
                    $customer->forceFill(['created_at' => $first])->saveQuietly();
                }
            }
            $created = Customer::withTrashed()->count() - $before;

            if ($dry) {
                DB::rollBack();
                $this->warn("Dry run: {$linked} records would be linked, {$created} customers created. Nothing was saved.");

                return self::SUCCESS;
            }
            Activity::record('customers_linked', null, ['linked records' => $linked, 'customers created' => $created], label: 'Existing bookings and enquiries');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->info("{$linked} records linked, {$created} customers created.");

        return self::SUCCESS;
    }
}
