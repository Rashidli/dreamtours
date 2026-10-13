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
];

foreach ($frontPages as $uri => $view) {
    Route::get($uri, [FrontPageController::class, 'show'])
        ->defaults('pageKey', $view)
        ->middleware('frontend.copy')
        ->name("front.{$view}");
}
Route::post('booking-requests', [FrontBookingController::class,'store'])->name('front.bookings.store');
Route::get('tours/{slug}', [TourPageController::class,'show'])->middleware('frontend.copy')->name('front.tours.show');
Route::get('tours/{slug}/book', [BookingPageController::class, 'create'])->middleware('frontend.copy')->name('front.tours.book');
Route::post('tours/{slug}/book', [BookingPageController::class, 'store'])->middleware('throttle:10,1')->name('front.tours.book.store');
Route::get('booking/{booking}', [BookingPageController::class, 'thankYou'])->middleware(['signed', 'frontend.copy'])->name('front.bookings.thank-you');
Route::post('tours/{slug}/reviews', [FrontReviewController::class, 'store'])->middleware('throttle:5,1')->name('front.reviews.store');

Route::prefix('admin')->group(function () {
    Route::get('/', [PageController::class, 'login'])->name('login');
    Route::get('/register', [PageController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login_submit'])->name('login_submit');
    Route::post('/register', [AuthController::class, 'register_submit'])->name('register_submit');

    Route::middleware('auth')->group(function () {
        Route::get('/home', [PageController::class, 'home'])->name('home');
        Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

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
        Route::resource('bookings', AdminBookingController::class)->only(['index','show','update','destroy']);
        Route::put('bookings/{booking}/seats', [AdminBookingController::class, 'updateSeats'])->name('bookings.seats');
        Route::post('bookings/{booking}/seat-notice', [AdminBookingController::class, 'sendSeatNotice'])->name('bookings.seat-notice');
        Route::get('bookings/{booking}/payment-proof', [AdminBookingController::class, 'paymentProof'])->name('bookings.payment-proof');
        Route::put('reviews/{review}/moderate', [ReviewController::class, 'moderate'])->name('reviews.moderate');
        Route::post('departures/{departure}/prices', [TourStructureController::class, 'storePrice'])->name('tour-departure-prices.store');
        Route::delete('departures/{departure}/prices/{price}', [TourStructureController::class, 'destroyPrice'])->name('tour-departure-prices.destroy');
        Route::resource('company-content', CompanyContentController::class)->except('show');
        Route::prefix('content/{resource}')->group(function(){Route::get('/',[DomainContentController::class,'index'])->name('domain-content.index');Route::get('create',[DomainContentController::class,'create'])->name('domain-content.create');Route::post('/',[DomainContentController::class,'store'])->name('domain-content.store');Route::get('{item}/edit',[DomainContentController::class,'edit'])->name('domain-content.edit');Route::put('{item}',[DomainContentController::class,'update'])->name('domain-content.update');Route::delete('{item}',[DomainContentController::class,'destroy'])->name('domain-content.destroy');});
        Route::post('gallery-albums/{album}/media',[DomainContentController::class,'storeMedia'])->name('gallery-media.store');
        Route::get('gallery-albums/{album}/media',[DomainContentController::class,'indexMedia'])->name('gallery-media.index');
        Route::get('gallery-albums/{album}/media/{medium}/edit',[DomainContentController::class,'editMedia'])->name('gallery-media.edit');
        Route::put('gallery-albums/{album}/media/{medium}',[DomainContentController::class,'updateMedia'])->name('gallery-media.update');
        Route::delete('gallery-albums/{album}/media/{medium}',[DomainContentController::class,'destroyMedia'])->name('gallery-media.destroy');
        Route::post('tours/{tour}/departures', [TourStructureController::class,'storeDeparture'])->name('tour-departures.store');
        Route::get('tours/{tour}/structure/{section}', [TourStructureController::class,'index'])->name('tour-structure.index');
        Route::put('tours/{tour}/departures/{departure}', [TourStructureController::class,'updateDeparture'])->name('tour-departures.update');
        Route::delete('tours/{tour}/departures/{departure}', [TourStructureController::class,'destroyDeparture'])->name('tour-departures.destroy');
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
