# DreamTour CMS plan

## Rules

- Initial content locale is `en`; the frontend has no language switcher.
- All translatable entities use `astrotomic/laravel-translatable` translation tables.
- Every admin resource has list/search filters, create, edit/update and delete actions.
- Every action has its own Spatie permission; `superadmin` bypasses checks through `Gate::before`.
- Repeated content is represented by related records, not embedded HTML or opaque JSON.

## Detected frontend map

1. Global: utility bar, logo, primary/mobile navigation, contact details, social links, CTA, footer columns, copyright.
2. Home: hero/search, featured tours, destinations, tour types, why choose us, reviews, gallery preview, CTA.
3. Tour catalogues: one-day, multi-day and international listing/filter pages; destination, month, transport, price and availability filters.
4. Private tours: hero, process steps, trip ideas, benefits, request form and CTA.
5. Cruises: hero/search, cruise filters and cards, benefits and CTA.
6. Visa support: hero/disclaimer, process steps, countries/services, documents, FAQ, request form and CTA.
7. About: story, statistics, values, team, partners, reviews and CTA.
8. Gallery: category filters, albums, media/lightbox and CTA.
9. Contact: contact form, contact details, map/pickup points, FAQ and CTA.
10. Shared operational data: enquiries, SEO metadata, reusable labels and status/order controls.

## Domain and relations

- `Page hasMany PageSection`; both have English translation records. Pages own SEO; sections own all headings, body and button copy.
- `NavigationMenu hasMany NavigationItem`; items support parent/child hierarchy.
- `TourCategory hasMany Tour`; tours belong to destinations and have many images, itinerary days, departures/prices and features.
- Tours and categories are many-to-many: one tour may simultaneously be multi-day, Canada, bus and seasonal.
- `Tour belongsToMany Transportation`; configurable vehicles store type, capacity, image and description.
- `Tour hasMany TourDeparture`; each departure owns date range, price/old price, capacity, reserved seats, status and availability.
- `Tour hasMany ItineraryDay hasMany ItineraryItem`; items support arrival/departure time, duration, address/map/video, photos, activities, notes and ticket inclusion.
- Included/excluded services, what-to-bring, policies, restrictions and FAQs are related editable records.
- `Booking belongsTo TourDeparture` and has many passengers; pickup point, meals, allergies, mobility, promo/source and special requests are retained.
- `Cruise` has many images, itineraries and departures/prices.
- `GalleryAlbum belongsTo GalleryCategory` and has many gallery media records.
- Gallery albums optionally belong to a tour and are filterable by tour, destination, year, month and category.
- Team members, partners, testimonials, FAQs, statistics, process steps and pickup points are reusable records assignable to a page/section.
- Contact/visa/private-tour forms create enquiry records in admin.
- Enquiries carry an explicit request type (`contact`, `tour`, `private_tour`, `visa`, `cruise`) for CRM filtering.

## Admin grouping

- Access: users, roles, permissions.
- Website: pages, sections, navigation, settings, socials, footer, SEO.
- Tours: categories, destinations, tours, features, itineraries, departures.
- Cruises: cruises, itineraries, departures.
- Content: about, team, partners, reviews, FAQs, gallery.
- Visa & enquiries: visa services/countries/documents, pickup points, submissions.

## Delivery order

1. Pages/sections and global website data.
2. Tour catalogues and their filters/relations.
3. Cruises and private tours.
4. About, gallery, reviews and FAQs.
5. Visa/contact forms and enquiries.
6. Replace all remaining static frontend strings and `data.js` collections, then run feature/permission tests.
