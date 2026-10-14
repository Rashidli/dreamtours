# DreamTour — Website Improvement Brief: completion report

Reference: *DreamTour_Website_Improvement_Developer_Brief.docx* and the owner's voice note (14 October 2026).
Staging site: https://tours.microcode.az — admin: https://tours.microcode.az/admin

Every item below was tested from the admin save through to the public page, on desktop (1280 px) and mobile (390 px), and re-checked after a page refresh.

## 1. Shared website controls

| ID | What was done | Where in admin |
|---|---|---|
| G01 | Every “Active” switch now hides the item on the website. A switched-off page section disappears completely (it no longer falls back to the template wording); a section whose items are all inactive is hidden too, so no empty heading is left. A switched-off page returns “not found”. Inactive items stay editable. | Each module’s “Active” checkbox · Website CMS → Page sections · Pages & SEO |
| G02 | The logo’s transparent margins were trimmed (they made it render at half size) and it is shown larger in the header, mobile menu and footer. New logo uploads are trimmed automatically. | Website CMS → Global settings → Logo |
| G03 | One shared motto band — “Don’t just dream places — go and see them.” — above the footer on every public page, including tour, booking, policy and Visa Support pages. Text after the dash is shown in brand pink. | Global settings → Company → Motto |

## 2. Homepage and footer

| ID | What was done | Where in admin |
|---|---|---|
| H01 | Cookie banner with **Accept all**, **Reject optional** and **Preferences**. Categories: Essential (always on), Preferences (saved trips and sort order kept between visits), Analytics, Marketing. The choice is kept for 12 months and can be changed any time from “Cookie settings” in the footer. Analytics / Meta tags load only after consent and only if an ID is entered. | Global settings → Analytics & cookies |
| H02 | “Homepage carousel” menu entry: add, replace, remove, reorder (Order number) and switch slides on/off, with image preview and alternative text. The carousel is full-width with the trip search below it. | Content → Homepage carousel |
| H03 | Fixed: approving a review now publishes it (homepage, About page and the related tour). Actions: Approve & publish, Reject, Unpublish (keeps the approval, hides the review). The customer review that was approved before the fix is now published. | Content → Reviews |
| H04 | Homepage Visa Support introduction with heading, text and “More Information” button to the Visa Support page. Text, button and visibility are editable. | Page sections → home / visa_intro |
| H05 | Footer Destinations block can be hidden and shown again without losing it. | Global settings → Company → Footer: Destinations block |
| H06 | Privacy Policy and Terms and Conditions pages with an editor, linked from the footer of every page and from the booking form’s consent checkboxes. **The wording is a placeholder until DreamTour supplies its policies.** | Page sections → privacy-policy / terms-and-conditions → content · Navigation & footer (footer links) · Global settings → Policies (booking form links) |

## 3. Tour listings, private tours and About Us

| ID | What was done | Where in admin |
|---|---|---|
| T01 | “Tour filters”: create filter groups (e.g. Theme) for One Day, Multi Day or both; add, rename, reorder, activate/deactivate and delete options. Assign options on each tour. The listing pages show only active options that at least one tour uses. Deleting a used option unassigns it from its tours and keeps the tours. | Tours & CRM → Tour filters · Tour page → Listing filters |
| P01 | Private Tours hero background and its three collage photos are replaceable, each with alternative text. | Page sections → private-tours / hero |
| P02 | “Private tour places”: name, tagline, image, description, link, order, active. Active places appear on the Private Tours page with a “Plan a trip here” button. | Content → Private tour places · Page sections → private-tours / places (heading) |
| A01 | Every About page image is editable: hero background, the three collage photos, the story photo, founder photo and team photos. | Page sections → about / hero and story · About content → founder · Team members |
| A02 | Team members marked “Always first (Founder / CEO)” lead the list; everyone else follows the Order number. New members go to the end. | Content → Team members |

## 4. Visa support and FAQs

| ID | What was done | Where in admin |
|---|---|---|
| V01 | “Visa Support Pricing”: country (typed or linked), service, DreamTour assistance fee and currency, optional government / third-party fee and note, processing time, active, order. Shown on the Visa Support page as “DreamTour assistance fee”, with third-party fees listed separately. | Tours & CRM → Visa Support Pricing |
| V02 | Dedicated visa enquiry form: full name, email, phone/WhatsApp with searchable country code, country, passport country, assistance required, applicants, travel date, question. It shows an enquiry reference (VE-…), emails the office, and is saved in its own list — it never creates a trip booking or touches seats. | Tours & CRM → Visa enquiries |
| F01 | FAQs are split into Trip FAQs (Contact page) and Visa Support FAQs (Visa Support page), each with its own order and active switch. All existing FAQs were visa questions and are in the visa group; Trip FAQs will show once added. | Content → FAQs |

## 5. Published trips and departure calendars

| ID | What was done | Where in admin |
|---|---|---|
| B01 | One trip, many dates: dates can be entered while creating the trip (“Add another date”) and added several at a time later. | Tour page → Departure dates |
| B02 | Each date has its own seat capacity, seats held offline, base price and price list, status (Open, Limited, Sold out, Closed, Hidden, Cancelled) and pickup points, and is edited on its own. Multi-day dates show start and end. A date with bookings cannot be deleted (close or hide it instead). | Tour page → Departure dates → Edit this date |
| B03 | Calendar on the trip page and on the booking page: available dates highlighted with their price, sold-out and closed dates disabled. Picking a date shows its prices, pickup points and seats. Scheduled trips show **Book Now**; a trip with no open dates shows “New dates coming soon”. | — |
| B04 | Each booking is saved against the trip and the chosen date; passengers are listed under that date. Seats are counted per date from live bookings, checked again inside a database lock on submission, and a full date becomes Sold out automatically. | Tour page → Departure dates → Passengers |

## 6. Booking form and admin

| ID | What was done | Where in admin |
|---|---|---|
| B05 | Steps: Date & travellers → Passengers (name, pickup point and meal for each person) → Contact (Canadian phone, email, WhatsApp with searchable country code, emergency contact) → Extras → Payment & agreements (deposit, JPG/PNG/HEIC/PDF payment proof up to 10 MB, required Terms and Privacy checkboxes, optional marketing consent stored separately, trip waiver when one is set) → **Review** with “Edit Booking Information” and “Confirm and Submit Booking”. The booking is created only on confirmation; double clicks and resubmissions create one booking. A confirmation with the booking reference follows. Payment proof is visible only to staff. | Tour page → Booking form (meal choices, waiver) |
| B06 | Booking list with search (reference, customer, passenger name, email, phone) and filters (trip, date, pickup point, status, payment). Booking page: reference, date, contact, WhatsApp, emergency contact, passengers with pickups and meals, payment proof, total / paid / balance, status (New, Payment pending, Confirmed, Cancelled, Completed), internal notes, last updated by, agreements and the exact waiver text accepted. Staff can edit contact details and passengers, add or remove passengers; cancelling releases the seats and re-confirming takes them back, refused if the date is full. | Tours & CRM → Bookings & enquiries |

## Items to confirm with DreamTour

- Final **Privacy Policy** and **Terms and Conditions** wording (placeholders are live now).
- Whether analytics / Meta Pixel should be used; if so, the IDs.
- Replacement images for the About page, Private Tours page and homepage carousel.
- Approval of the new logo size.
- Filter groups and options to create (none are set up yet).
- Visa service fees per country.
- Meal choices and waiver text for each trip.
- Private tour places and Trip FAQs to publish.
