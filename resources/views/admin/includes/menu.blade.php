<ul class="metismenu list-unstyled" id="side-menu">
    <li class="menu-title">DreamTour</li>
    <li><a href="{{route('home')}}"><i class="ri-dashboard-line"></i><span>Dashboard</span></a></li>

    @canany(['list-users','list-roles','list-permissions'])
    <li><a href="javascript:void(0)" class="has-arrow"><i class="ri-shield-user-line"></i><span>Access control</span></a><ul class="sub-menu">
        @can('list-users')<li><a href="{{route('users.index')}}">Users</a></li>@endcan
        @can('list-roles')<li><a href="{{route('roles.index')}}">Roles</a></li>@endcan
        @can('list-permissions')<li><a href="{{route('permissions.index')}}">Permissions</a></li>@endcan
    </ul></li>
    @endcanany

    <li class="menu-title">Website CMS</li>
    @can('list-pages')<li><a href="{{route('cms-pages.index')}}"><i class="ri-pages-line"></i><span>Pages & SEO</span></a></li>@endcan
    @can('list-page-sections')<li><a href="{{route('page-sections.index')}}"><i class="ri-layout-grid-line"></i><span>Page sections</span></a></li>@endcan
    @can('list-navigation')<li><a href="{{route('navigation-items.index')}}"><i class="ri-menu-line"></i><span>Navigation & footer</span></a></li>@endcan
    @can('list-settings')<li><a href="{{route('settings.index')}}"><i class="ri-settings-3-line"></i><span>Global settings</span></a></li>@endcan
    @can('list-words')<li><a href="{{route('words.index')}}"><i class="ri-translate-2"></i><span>UI copy</span></a></li>@endcan

    <li class="menu-title">Tours & CRM</li>
    @can('list-tours')<li><a href="{{route('tours.index')}}"><i class="ri-suitcase-line"></i><span>Tours & cruises</span></a></li>@endcan
    @can('list-tour-categories')<li><a href="{{route('tour-catalogue.index','categories')}}"><i class="ri-price-tag-3-line"></i><span>Tour categories</span></a></li>@endcan
    @can('list-tour-filters')<li><a href="{{route('tour-filters.index')}}"><i class="ri-filter-3-line"></i><span>Tour filters</span></a></li>@endcan
    @can('list-destinations')<li><a href="{{route('tour-catalogue.index','destinations')}}"><i class="ri-map-pin-line"></i><span>Destinations</span></a></li>@endcan
    @can('list-transportations')<li><a href="{{route('tour-catalogue.index','transportations')}}"><i class="ri-bus-line"></i><span>Transportation</span></a></li>@endcan
    @can('list-pickup-points')<li><a href="{{route('tour-catalogue.index','pickup-points')}}"><i class="ri-map-pin-2-line"></i><span>Pickup points</span></a></li>@endcan
    @can('list-bookings')<li><a href="{{route('bookings.index')}}"><i class="ri-calendar-check-line"></i><span>Bookings & enquiries</span></a></li>@endcan
    @can('list-visa-services')<li><a href="{{route('domain-content.index','visa-services')}}"><i class="ri-passport-line"></i><span>Visa Support Pricing</span></a></li>@endcan
    @can('list-visa-enquiries')<li><a href="{{route('visa-enquiries.index')}}"><i class="ri-question-answer-line"></i><span>Visa enquiries</span></a></li>@endcan

    <li class="menu-title">Content</li>
    @can('list-company-content')<li><a href="{{route('company-content.index',['type'=>'hero_slide'])}}"><i class="ri-slideshow-line"></i><span>Homepage carousel</span></a></li>@endcan
    @can('list-company-content')<li><a href="{{route('company-content.index',['type'=>'team'])}}"><i class="ri-team-line"></i><span>Team members</span></a></li>@endcan
    @can('list-company-content')<li><a href="{{route('company-content.index',['type'=>'private_place'])}}"><i class="ri-map-pin-line"></i><span>Private tour places</span></a></li>@endcan
    @can('list-company-content')<li><a href="{{route('company-content.index')}}"><i class="ri-apps-line"></i><span>Partners, stats & steps</span></a></li>@endcan
    @can('list-abouts')<li><a href="{{route('abouts.index')}}"><i class="ri-information-line"></i><span>About content</span></a></li>@endcan
    @can('list-reviews')<li><a href="{{route('reviews.index')}}"><i class="ri-star-line"></i><span>Reviews</span></a></li>@endcan
    @can('list-faqs')<li><a href="{{route('faqs.index')}}"><i class="ri-question-line"></i><span>FAQs</span></a></li>@endcan
    @can('list-gallery-albums')<li><a href="{{route('domain-content.index','gallery-albums')}}"><i class="ri-gallery-line"></i><span>Gallery albums</span></a></li>@endcan
    @can('list-gallery-categories')<li><a href="{{route('domain-content.index','gallery-categories')}}"><i class="ri-folder-image-line"></i><span>Gallery categories</span></a></li>@endcan
    @can('list-contact_items')<li><a href="{{route('contact_items.index')}}"><i class="ri-phone-line"></i><span>Contact information</span></a></li>@endcan
    @can('list-socials')<li><a href="{{route('socials.index')}}"><i class="ri-share-line"></i><span>Social links</span></a></li>@endcan
    @can('list-contacts')<li><a href="{{route('contacts.index')}}"><i class="ri-mail-line"></i><span>Contact requests</span></a></li>@endcan
</ul>
