<?php

namespace Database\Seeders;

use App\Models\CompanyContent;
use App\Models\Faq;
use App\Models\PageSection;
use App\Models\Tour;
use App\Models\TourFilter;
use App\Models\VisaService;
use Illuminate\Database\Seeder;

/**
 * Sample content for the features added from the October 2026 improvement brief, so every
 * new section has something to show until DreamTour supplies the real wording, prices and
 * photos: policy pages, private tour places, trip FAQs, visa fees, listing filters, meal
 * choices and waivers. Safe to run again — records are matched by name and updated.
 * Replace everything through the admin panel.
 */
class BriefDemoContentSeeder extends Seeder
{
    private const IMG = 'https://images.unsplash.com/photo-%s?q=80&w=1200&auto=format&fit=crop';

    public function run(): void
    {
        $this->policies();
        $this->privatePlaces();
        $this->tripFaqs();
        $this->visaFees();
        $this->filters();
        $this->mealsAndWaivers();
        $this->slideAltTexts();
    }

    private function policies(): void
    {
        $note = '<p><em>Sample wording — to be replaced with DreamTour’s final policy.</em></p>';
        $pages = [
            'privacy-policy' => $note.<<<'HTML'
<p>DreamTour (“we”, “us”) respects your privacy. This policy explains what personal information we collect when you book a trip or contact us, why we collect it and how we protect it.</p>
<h2>Information we collect</h2>
<ul><li>Contact details: name, email address, phone and WhatsApp number.</li><li>Traveller details: passenger names, dates of birth, pickup points, meal choices and any special requirements you tell us about.</li><li>An emergency contact for the trip.</li><li>Payment confirmations you upload. We do not store card numbers.</li></ul>
<h2>How we use it</h2>
<ul><li>To arrange your trip: seats, pickup, rooms, meals and communication before and during the tour.</li><li>To keep you safe and contact your emergency contact if needed.</li><li>To send news and offers — only if you agreed to marketing, and you can opt out at any time.</li></ul>
<h2>Sharing</h2>
<p>We share only what is needed with the hotels, transport and attraction partners of your trip. We never sell personal information.</p>
<h2>Cookies</h2>
<p>Essential cookies keep the site working. Preference, analytics and marketing cookies are used only with your consent; you can change your choice from “Cookie settings” at the bottom of every page.</p>
<h2>Keeping and deleting your data</h2>
<p>We keep booking records as long as required for accounting and legal purposes. You can ask us to see, correct or delete your information by contacting us.</p>
<h2>Contact</h2>
<p>Questions about this policy? Email us or call the number at the bottom of this page.</p>
HTML,
            'terms-and-conditions' => $note.<<<'HTML'
<p>These terms apply to every booking made with DreamTour. Please read them together with the deposit, cancellation and refund policy shown on each trip page.</p>
<h2>Booking and payment</h2>
<ul><li>A booking is confirmed once DreamTour has verified your deposit. Uploading a payment screenshot does not by itself confirm the booking.</li><li>The remaining balance is due 14 days before departure unless the trip page says otherwise.</li><li>Prices are per person in Canadian dollars and exclude tax unless stated.</li></ul>
<h2>Cancellations by you</h2>
<p>Deposits are non-refundable unless the trip page states otherwise. Cancellation charges for the balance depend on how close to departure you cancel, as set out on the trip page.</p>
<h2>Changes by DreamTour</h2>
<p>We may change the itinerary, pickup times or vehicle when necessary for safety or operational reasons. If we cancel a trip you can choose another date or receive a full refund of the amounts paid.</p>
<h2>On the trip</h2>
<ul><li>Please arrive at your pickup point 15 minutes early; the coach cannot wait for late passengers.</li><li>Bring valid photo ID, and a passport for trips that cross a border.</li><li>Follow the guide’s instructions. Some trips require a signed waiver.</li></ul>
<h2>Liability</h2>
<p>DreamTour arranges transport and services provided by independent suppliers and is not responsible for their acts or omissions beyond what the law requires. We recommend travel insurance.</p>
HTML,
        ];
        foreach ($pages as $page => $body) {
            $section = PageSection::where('key', 'content')->whereHas('page', fn ($q) => $q->where('key', $page))->first();
            if ($section) {
                $section->translateOrNew('en')->body = $body;
                $section->save();
            }
        }
    }

    private function privatePlaces(): void
    {
        $muskoka = Tour::whereTranslation('name', 'Muskoka')->value('main_image');
        $places = [
            ['Niagara Falls', 'Thundering falls and wine country', 'Stand at the brink of Horseshoe Falls, ride to the basin and finish with a tasting in Niagara-on-the-Lake.', sprintf(self::IMG, '1489447068241-b3490214e879')],
            ['Algonquin Park', 'Lakes, forests and wildlife', 'Short hikes, a canoe on a quiet lake and the best fall colours in Ontario — at your own pace.', sprintf(self::IMG, '1473448912268-2022ce9509d8')],
            ['Tobermory & Bruce Peninsula', 'Turquoise water and the Grotto', 'Glass-bottom boat over shipwrecks, Flowerpot Island and the cliffs of Georgian Bay.', sprintf(self::IMG, '1501785888041-af3ef285b470')],
            ['Québec City', 'Old-world charm, two days away', 'Cobblestone streets of Old Québec and the Montmorency Falls, with time for French cuisine.', sprintf(self::IMG, '1467269204594-9661b134dd2b')],
            ['Agawa Canyon', 'A scenic rail day', 'A full-day train ride through the Canadian Shield into the canyon — ideal for families and seniors.', sprintf(self::IMG, '1527684651001-731c474bbb5a')],
            ['Muskoka', 'Cottage country', 'Lakeside towns, a steamship cruise and fresh air two hours north of Toronto.', $muskoka],
        ];
        foreach ($places as $i => [$name, $tagline, $description, $image]) {
            $item = CompanyContent::where('type', 'private_place')->whereTranslation('title', $name)->first() ?? new CompanyContent(['type' => 'private_place']);
            $item->fill(['image' => $image, 'is_active' => true, 'sort_order' => $i + 1]);
            $item->translateOrNew('en')->title = $name;
            $item->translateOrNew('en')->subtitle = $tagline;
            $item->translateOrNew('en')->description = $description;
            $item->save();
        }
    }

    private function tripFaqs(): void
    {
        $faqs = [
            ['How do I book a trip?', 'Open the trip, pick a date in the calendar, choose the number of travellers and fill in each passenger’s details. You review everything before you confirm, and you receive a booking reference straight away.'],
            ['How much is the deposit and when is the balance due?', 'The deposit is shown on each trip page and on the booking form. The balance is due 14 days before departure unless the trip says otherwise.'],
            ['Can each passenger use a different pickup point?', 'Yes. On the booking form every passenger chooses their own pickup point from the ones available for that date.'],
            ['When do I get my seat number?', 'Seat numbers are emailed 1–2 days before departure once the booking is paid in full.'],
            ['Is there a children’s price?', 'Many trips have a child price for children under 12. Mark the passenger as a child on the booking form and the price is applied automatically.'],
            ['What happens if a trip is cancelled?', 'If DreamTour cancels a trip you can move to another date or receive a full refund of the amounts paid.'],
        ];
        foreach ($faqs as $i => [$question, $answer]) {
            $faq = Faq::where('group', 'trip')->whereTranslation('question', $question)->first() ?? new Faq(['group' => 'trip']);
            $faq->fill(['is_active' => true, 'order' => $i + 1]);
            $faq->translateOrNew('en')->question = $question;
            $faq->translateOrNew('en')->answer = $answer;
            $faq->save();
        }
    }

    private function visaFees(): void
    {
        $note = 'Government / visa centre fee, paid separately';
        $services = [
            ['Schengen Visa Support', 'Schengen Area (Europe)', 149, null, 'Embassy and VFS fees paid separately', '15 working days'],
            ['United States Visitor Visa', 'United States', 199, null, 'US$185 MRV fee paid to the US government', 'Interview date dependent'],
            ['UK Standard Visitor Visa', 'United Kingdom', 159, null, $note, '3 weeks'],
            ['China Tourist Visa (L)', 'China', 129, null, $note, '4–5 working days'],
            ['Japan Tourist Visa', 'Japan', 99, null, $note, '5–7 working days'],
            ['Australia Visitor Visa (600)', 'Australia', 119, null, $note, '2–4 weeks'],
        ];
        foreach ($services as $i => [$name, $country, $fee, $thirdParty, $thirdPartyNote, $processing]) {
            $service = VisaService::whereTranslation('name', $name)->first() ?? new VisaService;
            $service->fill([
                'destination_id' => null, 'country' => $country, 'price' => $fee, 'currency' => 'CAD',
                'third_party_fee' => $thirdParty, 'third_party_fee_note' => $thirdPartyNote,
                'processing_time' => $processing, 'is_active' => true, 'sort_order' => $i + 1,
            ]);
            $service->translateOrNew('en')->name = $name;
            $service->translateOrNew('en')->description ??= "We help you complete the {$country} application form, check your documents for completeness and book your appointment.";
            $service->translateOrNew('en')->required_documents ??= "Valid passport\nRecent photo\nProof of funds\nTravel itinerary";
            $service->save();
        }
    }

    private function filters(): void
    {
        $tours = Tour::with('translations')->get();
        $groups = [
            'Theme' => [
                'Nature & parks' => ['Tobermory & Kerim', 'Algonquin Park Fall Colours', 'Agawa Canyon Fall Train', 'Muskoka'],
                'Waterfalls' => ['Niagara Falls & Niagara-on-the-Lake', 'Québec City & Montmorency Falls'],
                'City & culture' => ['Québec City & Montmorency Falls', 'New York City Getaway'],
                'Food & wine' => ['Niagara Falls & Niagara-on-the-Lake'],
                'Scenic train' => ['Agawa Canyon Fall Train'],
            ],
            'Season' => [
                'Summer' => ['Tobermory & Kerim', 'Niagara Falls & Niagara-on-the-Lake', 'Muskoka', 'New York City Getaway'],
                'Fall colours' => ['Algonquin Park Fall Colours', 'Agawa Canyon Fall Train', 'Muskoka', 'Québec City & Montmorency Falls'],
            ],
        ];
        $sort = 0;
        foreach ($groups as $groupName => $options) {
            $group = TourFilter::updateOrCreate(['name' => $groupName], ['listing' => 'both', 'is_active' => true, 'sort_order' => ++$sort]);
            $optionSort = 0;
            foreach ($options as $optionName => $tourNames) {
                $option = $group->options()->updateOrCreate(['name' => $optionName], ['is_active' => true, 'sort_order' => ++$optionSort]);
                // Eloquent's only() filters by id, so match the names explicitly.
                $option->tours()->syncWithoutDetaching($tours->filter(fn (Tour $t) => in_array($t->name, $tourNames, true))->pluck('id'));
            }
        }
    }

    private function mealsAndWaivers(): void
    {
        $meals = "Tuna sandwich\nChicken sandwich\nVegetarian sandwich\nNo meal, thanks";
        $waiver = "I understand that this trip includes outdoor activities such as walking on uneven trails, boarding boats or trains and spending time near water, and that these carry some risk of injury.\n\n"
            ."I confirm that every passenger in my booking is fit to take part, will follow the guide's instructions and safety rules, and will not take part in any activity they do not feel able to do.\n\n"
            ."I accept responsibility for the passengers in my booking and release DreamTour from claims arising from risks that are part of these activities, except where DreamTour is at fault.\n\n"
            ."(Sample wording — to be replaced with DreamTour's final waiver.)";
        $outdoor = ['Tobermory & Kerim', 'Algonquin Park Fall Colours', 'Agawa Canyon Fall Train', 'Muskoka'];
        foreach (Tour::with('translations')->where('is_private', false)->where('is_cruise', false)->get() as $tour) {
            $t = $tour->translateOrNew('en');
            $t->meal_options = $meals;
            if (in_array($tour->name, $outdoor, true)) {
                $t->waiver_title = 'Release of Liability';
                $t->waiver = $waiver;
            }
            $tour->save();
        }
    }

    private function slideAltTexts(): void
    {
        foreach (CompanyContent::with('translations')->where('type', 'hero_slide')->get() as $slide) {
            $t = $slide->translateOrNew('en');
            $t->description ??= trim($slide->title.' — '.$slide->subtitle, ' —');
            $slide->save();
        }
    }
}
