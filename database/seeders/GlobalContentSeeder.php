<?php

namespace Database\Seeders;

use App\Models\ContactItem;
use App\Models\NavigationItem;
use App\Models\Setting;
use App\Models\Social;
use Illuminate\Database\Seeder;

class GlobalContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'site_name' => 'DreamTour', 'phone' => '416-726-6393', 'phone_link' => '+14167266393',
            'whatsapp' => '14167266393', 'email' => 'info@dreamtour.ca',
            'visa_email' => 'visa@dreamtour.ca', 'photos_email' => 'photos@dreamtour.ca',
            'working_hours' => 'Mon–Sun: 8:00 AM – 8:00 PM',
            'footer_description' => 'Comfortable tours, memorable journeys and friendly service across Canada and beyond.',
            'copyright' => '© '.date('Y').' DreamTour. All rights reserved.',
        ] as $key => $value) Setting::set($key, $value);

        $header = [
            ['Home', '/', null], ['Tours', '#', null], ['Private Tours', '/private-tours', null],
            ['Visa Support', '/visa-support', null], ['Gallery', '/gallery', null],
            ['About Us', '/about', null], ['Contact Us', '/contact', null],
        ];
        $parents = [];
        foreach ($header as $order => [$title, $url]) {
            $parents[$title] = NavigationItem::updateOrCreate(
                ['location' => 'header', 'url' => $url, 'parent_id' => null],
                ['sort_order' => $order, 'is_active' => true, 'target' => '_self', 'en' => ['title' => $title]]
            );
        }
        foreach ([['One-Day Tours', '/one-day-tours'], ['Multi-Day Tours', '/multi-day-tours'], ['International Tours', '/international-tours'], ['Cruises', '/cruises']] as $order => [$title, $url]) {
            NavigationItem::updateOrCreate(['location' => 'header', 'url' => $url], ['parent_id' => $parents['Tours']->id, 'sort_order' => $order, 'is_active' => true, 'target' => '_self', 'en' => ['title' => $title]]);
        }
        foreach ([
            'footer_company' => [['About Us','/about'],['Gallery','/gallery'],['Contact Us','/contact']],
            'footer_support' => [['Private Tours','/private-tours'],['Visa Support','/visa-support'],['Cruises','/cruises']],
            'footer_legal' => [['Privacy Policy','#'],['Terms & Conditions','#']],
        ] as $location => $links) foreach ($links as $order => [$title,$url]) {
            // Keyed on the title, not the URL: the legal links share '#', so
            // matching on the URL made the second overwrite the first.
            $item = NavigationItem::whereTranslation('title', $title)->where('location', $location)->first()
                ?? new NavigationItem(['location' => $location]);
            $item->fill(['location'=>$location,'url'=>$url,'sort_order'=>$order,'is_active'=>true,'target'=>'_self','en'=>['title'=>$title]])->save();
        }

        foreach ([
            ['Phone', '416-726-6393', 'tel:+14167266393', 'phone'],
            ['WhatsApp', '416-726-6393', 'https://wa.me/14167266393', 'whatsapp'],
            ['Email', 'info@dreamtour.ca', 'mailto:info@dreamtour.ca', 'mail'],
            ['Working hours', 'Mon–Sun: 8:00 AM – 8:00 PM', null, 'clock'],
        ] as $order => [$title,$value,$link,$icon]) ContactItem::updateOrCreate(['link'=>$link], ['icon'=>$icon,'order'=>$order,'is_active'=>true,'en'=>['title'=>$title,'value'=>$value]]);

        foreach ([['Facebook','facebook','#'],['Instagram','instagram','#'],['YouTube','youtube','#']] as $order => [$title,$icon,$link]) {
            Social::updateOrCreate(['title'=>$title], ['icon'=>$icon,'link'=>$link,'order'=>$order,'is_active'=>true]);
        }
    }
}
