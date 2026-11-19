{{-- Admin side menu, grouped by job: daily work first, then tours, the website and settings.
     Long lists (website content, catalogue setup, access) fold into sub-menus. Items of modules that
     are not deployed everywhere yet (vehicles, waitlist) only show when their route exists. --}}
<ul class="metismenu list-unstyled" id="side-menu">
    <li><a href="{{route('home')}}"><i class="ri-home-5-line"></i><span>Dashboard</span></a></li>
    @php($upcomingTrips = auth()->user()->tripAssignments()->whereHas('departure', fn ($q) => $q->where('departs_at', '>=', now()->startOfDay()))->distinct()->count('tour_departure_id'))
    @if($upcomingTrips || auth()->user()->can('list-assigned-departures'))
      <li><a href="{{route('my-trips.index')}}"><i class="ri-map-2-line"></i>@if($upcomingTrips)<span class="badge rounded-pill bg-primary float-end" title="Upcoming trips assigned to you">{{ $upcomingTrips }}</span>@endif<span>My trips</span></a></li>
    @endif

    @canany(['list-leads', 'list-bookings', 'list-customers', 'list-payments', 'list-visa-enquiries', 'list-contacts'])<li class="menu-title">Sales</li>@endcanany
    @can('list-leads')
      {{-- New leads and overdue reminders, so nothing waits unnoticed. --}}
      @php($leadsWaiting = \App\Models\Lead::whereHas('stage', fn ($q) => $q->where('key', 'new'))->count() + \App\Models\Lead::followUpDue()->count())
      <li><a href="{{route('leads.index')}}"><i class="ri-user-star-line"></i>@if($leadsWaiting)<span class="badge rounded-pill bg-danger float-end" title="New leads and follow-ups due">{{ $leadsWaiting }}</span>@endif<span>Leads</span></a></li>
    @endcan
    @can('list-bookings')<li><a href="{{route('bookings.index')}}"><i class="ri-calendar-check-line"></i><span>Bookings & enquiries</span></a></li>@endcan
    @if(Route::has('waitlist.index') && class_exists(\App\Models\WaitlistEntry::class))
      @can('list-bookings')
        @php($waiting = \App\Models\WaitlistEntry::where('status', 'waiting')->whereHas('departure', fn ($q) => $q->where('departs_at', '>=', now()))->count())
        <li><a href="{{route('waitlist.index')}}"><i class="ri-list-ordered"></i>@if($waiting)<span class="badge rounded-pill bg-secondary float-end" title="People waiting for a seat">{{ $waiting }}</span>@endif<span>Waitlist</span></a></li>
      @endcan
    @endif
    @can('list-customers')<li><a href="{{route('customers.index')}}"><i class="ri-contacts-book-line"></i><span>Customers</span></a></li>@endcan
    @can('list-payments')
      @php($paymentsWaiting = \App\Models\BookingPayment::where('status', 'pending')->count())
      <li><a href="{{route('payments.index')}}"><i class="ri-money-dollar-circle-line"></i>@if($paymentsWaiting)<span class="badge rounded-pill bg-warning float-end" title="Payments waiting for verification">{{ $paymentsWaiting }}</span>@endif<span>Payments & balances</span></a></li>
    @endcan
    @can('list-visa-enquiries')<li><a href="{{route('visa-enquiries.index')}}"><i class="ri-question-answer-line"></i><span>Visa enquiries</span></a></li>@endcan
    @can('list-contacts')<li><a href="{{route('contacts.index')}}"><i class="ri-mail-line"></i><span>Contact requests</span></a></li>@endcan

    @canany(['list-tours', 'list-tour-categories', 'list-tour-filters', 'list-inclusions', 'list-destinations', 'list-transportations', 'list-pickup-points', 'list-visa-services'])<li class="menu-title">Tours</li>@endcanany
    @can('list-tours')<li><a href="{{route('tours.index')}}"><i class="ri-suitcase-line"></i><span>Tours & cruises</span></a></li>@endcan
    @if(Route::has('vehicle-layouts.index'))
      @can('list-vehicle-layouts')<li><a href="{{route('vehicle-layouts.index')}}"><i class="ri-bus-2-line"></i><span>Vehicles & seating</span></a></li>@endcan
    @endif
    @can('list-destinations')<li><a href="{{route('tour-catalogue.index','destinations')}}"><i class="ri-map-pin-line"></i><span>Destinations</span></a></li>@endcan
    @canany(['list-tour-categories', 'list-tour-filters', 'list-inclusions', 'list-transportations', 'list-pickup-points'])
    <li><a href="javascript:void(0)" class="has-arrow"><i class="ri-list-settings-line"></i><span>Tour setup</span></a><ul class="sub-menu">
        @can('list-tour-categories')<li><a href="{{route('tour-catalogue.index','categories')}}">Categories</a></li>@endcan
        @can('list-tour-filters')<li><a href="{{route('tour-filters.index')}}">Listing filters</a></li>@endcan
        @can('list-inclusions')<li><a href="{{route('inclusions.index')}}">Inclusions</a></li>@endcan
        @can('list-transportations')<li><a href="{{route('tour-catalogue.index','transportations')}}">Transportation</a></li>@endcan
        @can('list-pickup-points')<li><a href="{{route('tour-catalogue.index','pickup-points')}}">Pickup points</a></li>@endcan
    </ul></li>
    @endcanany
    @can('list-visa-services')<li><a href="{{route('domain-content.index','visa-services')}}"><i class="ri-passport-line"></i><span>Visa support pricing</span></a></li>@endcan

    @canany(['list-pages', 'list-page-sections', 'list-navigation', 'list-legal-documents', 'list-company-content', 'list-abouts', 'list-reviews', 'list-faqs', 'list-gallery-albums', 'list-gallery-categories', 'list-contact_items', 'list-socials', 'list-words'])<li class="menu-title">Website</li>@endcanany
    @can('list-pages')<li><a href="{{route('cms-pages.index')}}"><i class="ri-pages-line"></i><span>Pages & SEO</span></a></li>@endcan
    @canany(['list-page-sections', 'list-company-content', 'list-abouts', 'list-reviews', 'list-faqs', 'list-contact_items', 'list-socials'])
    <li><a href="javascript:void(0)" class="has-arrow"><i class="ri-layout-masonry-line"></i><span>Content</span></a><ul class="sub-menu">
        @can('list-page-sections')<li><a href="{{route('page-sections.index')}}">Page sections</a></li>@endcan
        @can('list-company-content')<li><a href="{{route('company-content.index',['type'=>'hero_slide'])}}">Homepage carousel</a></li>@endcan
        @can('list-company-content')<li><a href="{{route('company-content.index',['type'=>'team'])}}">Team members</a></li>@endcan
        @can('list-company-content')<li><a href="{{route('company-content.index',['type'=>'private_place'])}}">Private tour places</a></li>@endcan
        @can('list-company-content')<li><a href="{{route('company-content.index')}}">Partners, stats & steps</a></li>@endcan
        @can('list-abouts')<li><a href="{{route('abouts.index')}}">About content</a></li>@endcan
        @can('list-reviews')<li><a href="{{route('reviews.index')}}">Reviews</a></li>@endcan
        @can('list-faqs')<li><a href="{{route('faqs.index')}}">FAQs</a></li>@endcan
        @can('list-contact_items')<li><a href="{{route('contact_items.index')}}">Contact information</a></li>@endcan
        @can('list-socials')<li><a href="{{route('socials.index')}}">Social links</a></li>@endcan
    </ul></li>
    @endcanany
    @canany(['list-gallery-albums', 'list-gallery-categories'])
    <li><a href="javascript:void(0)" class="has-arrow"><i class="ri-gallery-line"></i><span>Gallery</span></a><ul class="sub-menu">
        @can('list-gallery-albums')<li><a href="{{route('domain-content.index','gallery-albums')}}">Albums</a></li>@endcan
        @can('list-gallery-categories')<li><a href="{{route('domain-content.index','gallery-categories')}}">Categories</a></li>@endcan
    </ul></li>
    @endcanany
    @can('list-navigation')<li><a href="{{route('navigation-items.index')}}"><i class="ri-menu-line"></i><span>Navigation & footer</span></a></li>@endcan
    @can('list-legal-documents')<li><a href="{{route('legal-documents.index')}}"><i class="ri-file-shield-2-line"></i><span>Legal documents</span></a></li>@endcan

    @canany(['list-settings', 'list-words', 'list-bookings', 'list-users', 'list-roles', 'list-permissions', 'list-activity-log'])<li class="menu-title">Settings</li>@endcanany
    @can('list-settings')<li><a href="{{route('settings.index')}}"><i class="ri-settings-3-line"></i><span>General settings</span></a></li>@endcan
    @canany(['list-users','list-roles','list-permissions','list-activity-log'])
    <li><a href="javascript:void(0)" class="has-arrow"><i class="ri-shield-user-line"></i><span>Team & access</span></a><ul class="sub-menu">
        @can('list-users')<li><a href="{{route('users.index')}}">Users</a></li>@endcan
        @can('list-roles')<li><a href="{{route('roles.index')}}">Roles</a></li>@endcan
        @can('list-permissions')<li><a href="{{route('permissions.index')}}">Permissions</a></li>@endcan
        @can('list-activity-log')<li><a href="{{route('activity-log.index')}}">Activity history</a></li>@endcan
    </ul></li>
    @endcanany
    @can('list-bookings')<li><a href="{{route('email-previews.index')}}"><i class="ri-mail-open-line"></i><span>Email templates</span></a></li>@endcan
    @can('list-words')<li><a href="{{route('words.index')}}"><i class="ri-translate-2"></i><span>UI copy</span></a></li>@endcan
</ul>
