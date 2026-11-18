<?php

use App\Http\Controllers\Admin\AboutController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\ContactItemController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SocialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WordController;
use App\Http\Controllers\Admin\CmsPageController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\NavigationItemController;
use App\Http\Controllers\Admin\TourCatalogueController;
use App\Http\Controllers\Admin\TourController;
use App\Http\Controllers\Admin\TourStructureController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontPageController;
use App\Http\Controllers\BookingController as FrontBookingController;
use App\Http\Controllers\TourPageController;
use App\Http\Controllers\BookingPageController;
use App\Http\Controllers\ReviewController as FrontReviewController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DomainContentController;
use App\Http\Controllers\Admin\CompanyContentController;

$frontPages = [
    '/' => 'home',
    'about' => 'about',
    'contact' => 'contact',
    'cruises' => 'cruises',
    'favorites' => 'favorites',
    'gallery' => 'gallery',
    'international-tours' => 'international-tours',
    'multi-day-tours' => 'multi-day-tours',
    'one-day-tours' => 'one-day-tours',
    'private-tours' => 'private-tours',
    'visa-support' => 'visa-support',
    'privacy-policy' => 'privacy-policy',
    'terms-and-conditions' => 'terms-and-conditions',
    'refund-policy' => 'refund-policy',
    'waiver' => 'waiver',
];

foreach ($frontPages as $uri => $view) {
    Route::get($uri, [FrontPageController::class, 'show'])
        ->defaults('pageKey', $view)
        ->middleware('frontend.copy')
        ->name("front.{$view}");
}
Route::get('sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [\App\Http\Controllers\SitemapController::class, 'robots'])->name('robots');
Route::post('booking-requests', [FrontBookingController::class,'store'])->name('front.bookings.store');
// Project reports kept in docs/ (Markdown), shown in the browser as plain UTF-8 text.
Route::get('docs/{name}.md', function (string $name) {
    $path = base_path('docs/'.$name.'.md');
    abort_unless(preg_match('/^[A-Za-z0-9-]+$/', $name) && is_file($path), 404);
    return response()->file($path, ['Content-Type' => 'text/plain; charset=utf-8', 'X-Robots-Tag' => 'noindex']);
})->name('docs.markdown');
Route::post('visa-enquiries', [\App\Http\Controllers\VisaEnquiryController::class, 'store'])->middleware('throttle:10,1')->name('front.visa-enquiries.store');
Route::get('tours/{slug}', [TourPageController::class,'show'])->middleware('frontend.copy')->name('front.tours.show');
Route::get('tours/{slug}/book', [BookingPageController::class, 'create'])->middleware('frontend.copy')->name('front.tours.book');
Route::get('tours/{slug}/waitlist', [\App\Http\Controllers\WaitlistPageController::class, 'create'])->middleware('frontend.copy')->name('front.waitlist.create');
Route::post('tours/{slug}/waitlist', [\App\Http\Controllers\WaitlistPageController::class, 'store'])->middleware('throttle:6,1')->name('front.waitlist.store');
Route::post('tours/{slug}/book', [BookingPageController::class, 'store'])->middleware('throttle:10,1')->name('front.tours.book.store');
Route::get('booking/{booking}', [BookingPageController::class, 'thankYou'])->middleware(['signed', 'frontend.copy'])->name('front.bookings.thank-you');
Route::post('tours/{slug}/reviews', [FrontReviewController::class, 'store'])->middleware('throttle:5,1')->name('front.reviews.store');

Route::prefix('admin')->group(function () {
    // Someone already signed in goes straight to the dashboard (redirectUsersTo in bootstrap/app.php).
    Route::get('/', [PageController::class, 'login'])->middleware('guest')->name('login');
    Route::post('/login', [AuthController::class, 'login_submit'])->middleware('guest')->name('login_submit');

    Route::middleware('auth')->group(function () {
        Route::get('/home', [PageController::class, 'home'])->name('home');
        Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('activity-history', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-log.index');

        Route::resources([
            'users' => UserController::class,
            'roles' => RoleController::class,
            'permissions' => PermissionController::class,
            'faqs' => FaqController::class,
            'reviews' => ReviewController::class,
            'abouts' => AboutController::class,
            'contact_items' => ContactItemController::class,
            'socials' => SocialController::class,
        ]);
        // Copy rows are generated from the templates by UiCopySeeder; only the wording is editable.
        Route::resource('words', WordController::class)->only(['index', 'edit', 'update']);

        // Pages are bound to the front-end routes and blade templates, so only their content is editable.
        Route::resource('cms-pages', CmsPageController::class)->only(['index', 'edit', 'update']);
        Route::resource('page-sections', PageSectionController::class)->only(['index', 'edit', 'update']);
        Route::resource('navigation-items', NavigationItemController::class)->except('show');
        Route::resource('tours', TourController::class)->except('show');
        Route::post('tours/{tour}/duplicate', [\App\Http\Controllers\Admin\TourDuplicateController::class, 'store'])->name('tours.duplicate');
        Route::get('tour-filters', [\App\Http\Controllers\Admin\TourFilterController::class, 'index'])->name('tour-filters.index');
        Route::resource('inclusions', \App\Http\Controllers\Admin\InclusionController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('tour-filters', [\App\Http\Controllers\Admin\TourFilterController::class, 'store'])->name('tour-filters.store');
        Route::put('tour-filters/{tour_filter}', [\App\Http\Controllers\Admin\TourFilterController::class, 'update'])->name('tour-filters.update');
        Route::delete('tour-filters/{tour_filter}', [\App\Http\Controllers\Admin\TourFilterController::class, 'destroy'])->name('tour-filters.destroy');
        Route::post('tour-filters/{tour_filter}/options', [\App\Http\Controllers\Admin\TourFilterController::class, 'storeOption'])->name('tour-filter-options.store');
        Route::put('tour-filter-options/{option}', [\App\Http\Controllers\Admin\TourFilterController::class, 'updateOption'])->name('tour-filter-options.update');
        Route::delete('tour-filter-options/{option}', [\App\Http\Controllers\Admin\TourFilterController::class, 'destroyOption'])->name('tour-filter-options.destroy');
        // Staff-made bookings (CRM §6); registered before the resource so "new" is not read as a booking id.
        Route::get('bookings/new', [\App\Http\Controllers\Admin\ManualBookingController::class, 'create'])->name('bookings.create');
        Route::post('bookings', [\App\Http\Controllers\Admin\ManualBookingController::class, 'store'])->name('bookings.store');
        Route::post('bookings/{booking}/passengers/{passenger}/move', [\App\Http\Controllers\Admin\ManualBookingController::class, 'movePassenger'])->name('booking-passengers.move');
        Route::post('bookings/{booking}/passengers/{passenger}/cancel', [\App\Http\Controllers\Admin\ManualBookingController::class, 'cancelPassenger'])->name('booking-passengers.cancel');
        Route::get('bookings/customer-lookup', [\App\Http\Controllers\Admin\ManualBookingController::class, 'customers'])->name('bookings.customer-lookup');
        Route::resource('bookings', AdminBookingController::class)->only(['index','show','update','destroy']);
        Route::get('payments', [\App\Http\Controllers\Admin\PaymentReportController::class, 'index'])->name('payments.index');
        Route::get('bookings/{booking}/statement', [\App\Http\Controllers\Admin\ReceiptController::class, 'statement'])->name('receipts.statement');
        Route::get('bookings/{booking}/payments/{payment}/receipt', [\App\Http\Controllers\Admin\ReceiptController::class, 'payment'])->name('receipts.payment');
        Route::post('bookings/{booking}/payments', [\App\Http\Controllers\Admin\BookingPaymentController::class, 'store'])->name('booking-payments.store');
        Route::post('bookings/{booking}/payments/{payment}/confirm', [\App\Http\Controllers\Admin\BookingPaymentController::class, 'confirm'])->name('booking-payments.confirm');
        Route::post('bookings/{booking}/payments/{payment}/reject', [\App\Http\Controllers\Admin\BookingPaymentController::class, 'reject'])->name('booking-payments.reject');
        Route::post('bookings/{booking}/payments/{payment}/refund', [\App\Http\Controllers\Admin\BookingPaymentController::class, 'refund'])->name('booking-payments.refund');
        Route::post('bookings/{booking}/payments/{payment}/void', [\App\Http\Controllers\Admin\BookingPaymentController::class, 'void'])->name('booking-payments.void');
        Route::get('bookings/{booking}/payments/{payment}/proof', [\App\Http\Controllers\Admin\BookingPaymentController::class, 'proof'])->name('booking-payments.proof');
        Route::post('bookings/{booking}/adjustments', [\App\Http\Controllers\Admin\BookingPaymentController::class, 'adjust'])->name('booking-adjustments.store');
        Route::post('bookings/{booking}/adjustments/{adjustment}/void', [\App\Http\Controllers\Admin\BookingPaymentController::class, 'voidAdjustment'])->name('booking-adjustments.void');
        Route::put('bookings/{booking}/payment-terms', [\App\Http\Controllers\Admin\BookingPaymentController::class, 'terms'])->name('booking-payments.terms');
        Route::get('customers/labels', [\App\Http\Controllers\Admin\CustomerLabelController::class, 'index'])->name('customer-labels.index');
        Route::post('customers/labels/{kind}', [\App\Http\Controllers\Admin\CustomerLabelController::class, 'store'])->name('customer-labels.store');
        Route::put('customers/labels/{kind}/{id}', [\App\Http\Controllers\Admin\CustomerLabelController::class, 'update'])->name('customer-labels.update');
        Route::post('customers/labels/{kind}/{id}/archive', [\App\Http\Controllers\Admin\CustomerLabelController::class, 'archive'])->name('customer-labels.archive');
        Route::delete('customers/labels/{kind}/{id}', [\App\Http\Controllers\Admin\CustomerLabelController::class, 'destroy'])->name('customer-labels.destroy');
        Route::get('leads/stages', [\App\Http\Controllers\Admin\LeadStageController::class, 'index'])->name('lead-stages.index');
        Route::post('leads/stages', [\App\Http\Controllers\Admin\LeadStageController::class, 'store'])->name('lead-stages.store');
        Route::put('leads/stages/{stage}', [\App\Http\Controllers\Admin\LeadStageController::class, 'update'])->name('lead-stages.update');
        Route::post('leads/stages/{stage}/move', [\App\Http\Controllers\Admin\LeadStageController::class, 'move'])->name('lead-stages.move');
        Route::post('leads/stages/{stage}/archive', [\App\Http\Controllers\Admin\LeadStageController::class, 'archive'])->name('lead-stages.archive');
        Route::delete('leads/stages/{stage}', [\App\Http\Controllers\Admin\LeadStageController::class, 'destroy'])->name('lead-stages.destroy');
        Route::patch('leads/{lead}/stage', [\App\Http\Controllers\Admin\LeadController::class, 'stage'])->name('leads.stage');
        Route::patch('leads/{lead}/assign', [\App\Http\Controllers\Admin\LeadController::class, 'assign'])->name('leads.assign');
        Route::patch('leads/{lead}/follow-up', [\App\Http\Controllers\Admin\LeadController::class, 'followUp'])->name('leads.follow-up');
        Route::post('leads/{lead}/notes', [\App\Http\Controllers\Admin\LeadController::class, 'note'])->name('leads.note');
        Route::post('leads/{lead}/convert', [\App\Http\Controllers\Admin\LeadController::class, 'convert'])->name('leads.convert');
        Route::put('leads/{lead}/tags', [\App\Http\Controllers\Admin\LeadController::class, 'tags'])->name('leads.tags');
        Route::resource('leads', \App\Http\Controllers\Admin\LeadController::class)->except(['edit']);
        Route::get('customers/import', [\App\Http\Controllers\Admin\CustomerImportController::class, 'create'])->name('customer-imports.create');
        Route::post('customers/import', [\App\Http\Controllers\Admin\CustomerImportController::class, 'store'])->name('customer-imports.store');
        Route::get('customers/import/{import}', [\App\Http\Controllers\Admin\CustomerImportController::class, 'show'])->name('customer-imports.show');
        Route::get('customers/import/{import}/map', [\App\Http\Controllers\Admin\CustomerImportController::class, 'edit'])->name('customer-imports.edit');
        Route::put('customers/import/{import}/map', [\App\Http\Controllers\Admin\CustomerImportController::class, 'update'])->name('customer-imports.update');
        Route::post('customers/import/{import}/commit', [\App\Http\Controllers\Admin\CustomerImportController::class, 'commit'])->name('customer-imports.commit');
        Route::get('customers/import/{import}/problems', [\App\Http\Controllers\Admin\CustomerImportController::class, 'problems'])->name('customer-imports.problems');
        Route::get('customers/duplicates', [\App\Http\Controllers\Admin\CustomerMergeController::class, 'index'])->name('customer-merge.index');
        Route::post('customers/duplicates/dismiss', [\App\Http\Controllers\Admin\CustomerMergeController::class, 'dismiss'])->name('customer-merge.dismiss');
        Route::get('customers/merge', [\App\Http\Controllers\Admin\CustomerMergeController::class, 'compare'])->name('customer-merge.compare');
        Route::post('customers/merge', [\App\Http\Controllers\Admin\CustomerMergeController::class, 'merge'])->name('customer-merge.merge');
        Route::post('customers/{customer}/restore', [\App\Http\Controllers\Admin\CustomerController::class, 'restore'])->name('customers.restore');
        Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)->withTrashed(['show']);
        Route::get('email-previews', [\App\Http\Controllers\Admin\EmailPreviewController::class, 'index'])->name('email-previews.index');
        Route::get('email-previews/{key}', [\App\Http\Controllers\Admin\EmailPreviewController::class, 'show'])->name('email-previews.show');
        Route::resource('visa-enquiries', \App\Http\Controllers\Admin\VisaEnquiryController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::get('legal-documents', [\App\Http\Controllers\Admin\LegalDocumentController::class, 'index'])->name('legal-documents.index');
        Route::get('legal-documents/{legal_document}/edit', [\App\Http\Controllers\Admin\LegalDocumentController::class, 'edit'])->name('legal-documents.edit');
        Route::put('legal-documents/{legal_document}', [\App\Http\Controllers\Admin\LegalDocumentController::class, 'update'])->name('legal-documents.update');
        Route::get('legal-document-versions/{version}', [\App\Http\Controllers\Admin\LegalDocumentController::class, 'version'])->name('legal-documents.version');
        Route::put('bookings/{booking}/details', [AdminBookingController::class, 'updateDetails'])->name('bookings.details');
        Route::put('bookings/{booking}/seats', [AdminBookingController::class, 'updateSeats'])->name('bookings.seats');
        Route::post('bookings/{booking}/seat-notice', [AdminBookingController::class, 'sendSeatNotice'])->name('bookings.seat-notice');
        Route::get('bookings/{booking}/payment-proof', [AdminBookingController::class, 'paymentProof'])->name('bookings.payment-proof');
        Route::put('reviews/{review}/moderate', [ReviewController::class, 'moderate'])->name('reviews.moderate');
        Route::post('departures/{departure}/prices', [TourStructureController::class, 'storePrice'])->name('tour-departure-prices.store');
        Route::delete('departures/{departure}/prices/{price}', [TourStructureController::class, 'destroyPrice'])->name('tour-departure-prices.destroy');
        Route::resource('vehicle-layouts', \App\Http\Controllers\Admin\VehicleLayoutController::class)->except('show');
        Route::resource('company-content', CompanyContentController::class)->except('show');
        Route::prefix('content/{resource}')->group(function(){Route::get('/',[DomainContentController::class,'index'])->name('domain-content.index');Route::get('create',[DomainContentController::class,'create'])->name('domain-content.create');Route::post('/',[DomainContentController::class,'store'])->name('domain-content.store');Route::get('{item}/edit',[DomainContentController::class,'edit'])->name('domain-content.edit');Route::put('{item}',[DomainContentController::class,'update'])->name('domain-content.update');Route::delete('{item}',[DomainContentController::class,'destroy'])->name('domain-content.destroy');});
        Route::post('gallery-albums/{album}/media',[DomainContentController::class,'storeMedia'])->name('gallery-media.store');
        Route::get('gallery-albums/{album}/media',[DomainContentController::class,'indexMedia'])->name('gallery-media.index');
        Route::get('gallery-albums/{album}/media/{medium}/edit',[DomainContentController::class,'editMedia'])->name('gallery-media.edit');
        Route::put('gallery-albums/{album}/media/{medium}',[DomainContentController::class,'updateMedia'])->name('gallery-media.update');
        Route::delete('gallery-albums/{album}/media/{medium}',[DomainContentController::class,'destroyMedia'])->name('gallery-media.destroy');
        Route::post('tours/{tour}/departures', [\App\Http\Controllers\Admin\TourDepartureController::class,'store'])->name('tour-departures.store');
        Route::get('tours/{tour}/structure/{section}', [TourStructureController::class,'index'])->name('tour-structure.index');
        Route::put('tours/{tour}/departures/{departure}', [\App\Http\Controllers\Admin\TourDepartureController::class,'update'])->name('tour-departures.update');
        Route::get('departures/{departure}/passengers', [\App\Http\Controllers\Admin\RosterController::class, 'index'])->name('tour-departures.passengers');
        Route::post('departures/{departure}/passengers/{passenger}/attendance', [\App\Http\Controllers\Admin\RosterController::class, 'attendance'])->middleware('throttle:120,1')->name('passenger-attendance.update');
        Route::post('departures/{departure}/staff', [\App\Http\Controllers\Admin\DepartureStaffController::class, 'store'])->name('departure-staff.store');
        Route::delete('departures/{departure}/staff/{assignment}', [\App\Http\Controllers\Admin\DepartureStaffController::class, 'destroy'])->name('departure-staff.destroy');
        Route::get('departures/{departure}/seating', [\App\Http\Controllers\Admin\SeatingController::class, 'show'])->name('seating.show');
        Route::middleware('throttle:240,1')->group(function () {
            Route::post('departures/{departure}/seating/place', [\App\Http\Controllers\Admin\SeatingController::class, 'place'])->name('seating.place');
            Route::post('departures/{departure}/seating/block', [\App\Http\Controllers\Admin\SeatingController::class, 'block'])->name('seating.block');
            Route::post('departures/{departure}/seating/autofill', [\App\Http\Controllers\Admin\SeatingController::class, 'autofill'])->name('seating.autofill');
        });
        Route::post('departures/{departure}/seating/publish', [\App\Http\Controllers\Admin\SeatingController::class, 'publish'])->name('seating.publish');
        Route::get('departures/{departure}/seating/export/{format}', [\App\Http\Controllers\Admin\SeatingController::class, 'export'])->name('seating.export');
        Route::post('departures/{departure}/vehicles', [\App\Http\Controllers\Admin\SeatingController::class, 'storeVehicle'])->name('departure-vehicles.store');
        Route::put('departures/{departure}/vehicles/{vehicle}', [\App\Http\Controllers\Admin\SeatingController::class, 'updateVehicle'])->name('departure-vehicles.update');
        Route::delete('departures/{departure}/vehicles/{vehicle}', [\App\Http\Controllers\Admin\SeatingController::class, 'destroyVehicle'])->name('departure-vehicles.destroy');
        Route::get('waitlist', [\App\Http\Controllers\Admin\WaitlistController::class, 'index'])->name('waitlist.index');
        Route::post('waitlist', [\App\Http\Controllers\Admin\WaitlistController::class, 'store'])->name('waitlist.store');
        Route::post('waitlist/{entry}/offer', [\App\Http\Controllers\Admin\WaitlistController::class, 'offer'])->name('waitlist.offer');
        Route::post('waitlist/{entry}/remove', [\App\Http\Controllers\Admin\WaitlistController::class, 'remove'])->name('waitlist.remove');
        Route::post('waitlist/{entry}/move', [\App\Http\Controllers\Admin\WaitlistController::class, 'move'])->name('waitlist.move');
        Route::get('alerts', [\App\Http\Controllers\Admin\AlertController::class, 'index'])->name('alerts.index');
        Route::get('alerts/dropdown', [\App\Http\Controllers\Admin\AlertController::class, 'dropdown'])->name('alerts.dropdown');
        Route::post('alerts/done', [\App\Http\Controllers\Admin\AlertController::class, 'completeAll'])->name('alerts.complete-all');
        Route::post('alerts/settings', [\App\Http\Controllers\Admin\AlertController::class, 'settings'])->name('alerts.settings');
        Route::post('alerts/{alert}/done', [\App\Http\Controllers\Admin\AlertController::class, 'complete'])->name('alerts.complete');
        Route::get('my-trips', [\App\Http\Controllers\Admin\MyTripsController::class, 'index'])->name('my-trips.index');
        Route::get('my-trips/{departure}', [\App\Http\Controllers\Admin\MyTripsController::class, 'show'])->name('my-trips.show');
        Route::delete('tours/{tour}/departures/{departure}', [\App\Http\Controllers\Admin\TourDepartureController::class,'destroy'])->name('tour-departures.destroy');
        Route::post('tours/{tour}/itinerary-days', [TourStructureController::class,'storeDay'])->name('tour-itinerary-days.store');
        Route::put('tours/{tour}/itinerary-days/{day}', [TourStructureController::class,'updateDay'])->name('tour-itinerary-days.update');
        Route::delete('tours/{tour}/itinerary-days/{day}', [TourStructureController::class,'destroyDay'])->name('tour-itinerary-days.destroy');
        Route::post('itinerary-days/{day}/items', [TourStructureController::class,'storeItem'])->name('tour-itinerary-items.store');
        Route::put('itinerary-days/{day}/items/{item}', [TourStructureController::class,'updateItem'])->name('tour-itinerary-items.update');
        Route::delete('itinerary-days/{day}/items/{item}', [TourStructureController::class,'destroyItem'])->name('tour-itinerary-items.destroy');
        Route::post('tours/{tour}/content-items', [TourStructureController::class,'storeContent'])->name('tour-content-items.store');
        Route::put('tours/{tour}/content-items/{content}', [TourStructureController::class,'updateContent'])->name('tour-content-items.update');
        Route::delete('tours/{tour}/content-items/{content}', [TourStructureController::class,'destroyContent'])->name('tour-content-items.destroy');
        Route::prefix('tour-catalogue/{resource}')->group(function () {
            Route::get('/', [TourCatalogueController::class, 'index'])->name('tour-catalogue.index');
            Route::get('/create', [TourCatalogueController::class, 'create'])->name('tour-catalogue.create');
            Route::post('/', [TourCatalogueController::class, 'store'])->name('tour-catalogue.store');
            Route::get('/{item}/edit', [TourCatalogueController::class, 'edit'])->name('tour-catalogue.edit');
            Route::put('/{item}', [TourCatalogueController::class, 'update'])->name('tour-catalogue.update');
            Route::delete('/{item}', [TourCatalogueController::class, 'destroy'])->name('tour-catalogue.destroy');
        });

        Route::resource('contacts', ContactController::class)->only(['index', 'show', 'destroy']);
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
