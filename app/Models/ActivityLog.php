<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * One entry of the append-only activity history. Entries are written through
 * App\Support\Activity and can never be changed or deleted from the application.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /** subject class => [module label, admin route that opens the record] */
    public const MODULES = [
        Booking::class => ['Bookings', 'bookings.show'],
        BookingPassenger::class => ['Passengers', null],
        Tour::class => ['Tours', 'tours.edit'],
        TourDeparture::class => ['Departures', null],
        TourDeparturePrice::class => ['Departure prices', null],
        TourItineraryDay::class => ['Itinerary', null],
        TourItineraryItem::class => ['Itinerary', null],
        TourContentItem::class => ['Tour content', null],
        PickupPoint::class => ['Pickup points', null],
        User::class => ['Users', 'users.edit'],
        \Spatie\Permission\Models\Role::class => ['Roles', 'roles.edit'],
        Setting::class => ['Settings', null],
        LegalDocument::class => ['Legal documents', null],
        LegalDocumentVersion::class => ['Legal documents', null],
        Review::class => ['Reviews', 'reviews.edit'],
        GalleryAlbum::class => ['Gallery', null],
        GalleryMedia::class => ['Gallery', null],
        Destination::class => ['Destinations', null],
        TourCategory::class => ['Tour categories', null],
        Inclusion::class => ['Inclusions', null],
        CompanyContent::class => ['Company content', null],
        PageSection::class => ['Page sections', null],
        VisaEnquiry::class => ['Visa enquiries', 'visa-enquiries.show'],
        VisaService::class => ['Visa services', null],
        Contact::class => ['Contact requests', null],
    ];

    protected $guarded = ['id'];

    protected $casts = ['changes' => 'array', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Activity history is append-only.'));
        static::deleting(fn () => throw new LogicException('Activity history is append-only.'));
    }

    public function moduleLabel(): string
    {
        return self::MODULES[$this->subject_type][0] ?? class_basename((string) $this->subject_type) ?: 'General';
    }

    /** Admin link to the record when it has a page (none for deletions). */
    public function subjectUrl(): ?string
    {
        $route = self::MODULES[$this->subject_type][1] ?? null;

        return $route && $this->subject_id && $this->action !== 'deleted' && \Route::has($route) ? route($route, $this->subject_id) : null;
    }
}
