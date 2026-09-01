<?php

namespace Database\Seeders;

use App\Models\About;
use App\Models\Blog;
use App\Models\Faq;
use App\Models\Portfolio;
use App\Models\PortfolioImage;
use App\Models\Review;
use App\Models\Service;
use App\Models\Slider;
use App\Models\Tag;
use App\Models\WhyUs;
use App\Models\ContactItem;
use App\Models\Social;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate existing data
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Slider::query()->forceDelete();
        \DB::table('slider_translations')->truncate();

        Service::query()->forceDelete();
        \DB::table('service_translations')->truncate();

        Portfolio::query()->forceDelete();
        \DB::table('portfolio_translations')->truncate();
        PortfolioImage::query()->forceDelete();

        Blog::query()->forceDelete();
        \DB::table('blog_translations')->truncate();
        \DB::table('blog_tag')->truncate();

        Tag::query()->forceDelete();
        \DB::table('tag_translations')->truncate();

        Faq::query()->forceDelete();
        \DB::table('faq_translations')->truncate();

        Review::query()->forceDelete();
        \DB::table('review_translations')->truncate();

        About::query()->forceDelete();
        \DB::table('about_translations')->truncate();

        WhyUs::query()->forceDelete();
        \DB::table('why_us_translations')->truncate();

        ContactItem::query()->forceDelete();
        \DB::table('contact_item_translations')->truncate();

        Social::query()->forceDelete();

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->seedSliders();
        $this->seedServices();
        $this->seedPortfolios();
        $this->seedBlogs();
        $this->seedFaqs();
        $this->seedReviews();
        $this->seedAbout();
        $this->seedWhyUs();
        $this->seedContactItems();
        $this->seedSocials();
    }

    private function downloadImage($url, $folder, $filename)
    {
        try {
            $imageContent = file_get_contents($url);
            $path = "{$folder}/{$filename}";
            Storage::disk('public')->put($path, $imageContent);
            return $path;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function seedSliders()
    {
        $sliders = [
            [
                'image_url' => 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=1920&h=800&fit=crop',
                'az' => [
                    'subtitle' => 'Peşəkar Xidmət',
                    'title' => 'Santexnik Ustası',
                    'description' => 'Eviniz üçün peşəkar santexnik xidmətləri. 24/7 təcili çağırış xidməti.',
                    'button_text' => 'Əlaqə'
                ],
                'en' => [
                    'subtitle' => 'Professional Service',
                    'title' => 'Plumbing Master',
                    'description' => 'Professional plumbing services for your home. 24/7 emergency call service.',
                    'button_text' => 'Contact'
                ],
                'ru' => [
                    'subtitle' => 'Профессиональный сервис',
                    'title' => 'Мастер Сантехник',
                    'description' => 'Профессиональные сантехнические услуги для вашего дома. Экстренный вызов 24/7.',
                    'button_text' => 'Контакт'
                ],
                'button_link' => '/elaqe',
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1607472586893-edb57bdc0e39?w=1920&h=800&fit=crop',
                'az' => [
                    'subtitle' => 'Təcrübəli Komanda',
                    'title' => 'Keyfiyyətli Təmir',
                    'description' => '10+ illik təcrübə ilə bütün növ santexnik işləri. Zəmanətli xidmət.',
                    'button_text' => 'Xidmətlər'
                ],
                'en' => [
                    'subtitle' => 'Experienced Team',
                    'title' => 'Quality Repair',
                    'description' => 'All types of plumbing work with 10+ years of experience. Guaranteed service.',
                    'button_text' => 'Services'
                ],
                'ru' => [
                    'subtitle' => 'Опытная команда',
                    'title' => 'Качественный ремонт',
                    'description' => 'Все виды сантехнических работ с 10+ летним опытом. Гарантированный сервис.',
                    'button_text' => 'Услуги'
                ],
                'button_link' => '/xidmetler',
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1920&h=800&fit=crop',
                'az' => [
                    'subtitle' => 'Sürətli Xidmət',
                    'title' => 'Təcili Təmir',
                    'description' => 'Boru sızması, tıxanma, qızdırıcı nasazlığı - hər problemi həll edirik!',
                    'button_text' => 'Zəng Et'
                ],
                'en' => [
                    'subtitle' => 'Fast Service',
                    'title' => 'Emergency Repair',
                    'description' => 'Pipe leaks, blockages, heater malfunctions - we solve every problem!',
                    'button_text' => 'Call Now'
                ],
                'ru' => [
                    'subtitle' => 'Быстрый сервис',
                    'title' => 'Срочный ремонт',
                    'description' => 'Протечка труб, засоры, неисправность нагревателя - решаем любую проблему!',
                    'button_text' => 'Позвонить'
                ],
                'button_link' => 'tel:+994501234567',
            ],
        ];

        foreach ($sliders as $index => $data) {
            $imagePath = $this->downloadImage(
                $data['image_url'],
                'sliders',
                'slider-' . ($index + 1) . '.jpg'
            );

            Slider::create([
                'image' => $imagePath,
                'button_link' => $data['button_link'],
                'is_active' => true,
                'order' => $index + 1,
                'az' => $data['az'],
                'en' => $data['en'],
                'ru' => $data['ru'],
            ]);
        }
    }

    private function seedServices()
    {
        $services = [
            [
                'image_url' => 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=800&h=600&fit=crop',
                'icon' => 'ri-drop-line',
                'az' => [
                    'title' => 'Boru Təmiri',
                    'slug' => 'boru-temiri',
                    'short_description' => 'Bütün növ boruların təmiri və dəyişdirilməsi xidməti.',
                    'description' => '<p>Peşəkar boru təmiri xidmətimiz ev və ofislərdə yaranan bütün boru problemlərini həll edir.</p><h3>Xidmətlərimiz:</h3><ul><li>Sızan boruların təmiri</li><li>Köhnə boruların dəyişdirilməsi</li><li>Plastik və metal boru quraşdırılması</li><li>Kanalizasiya borularının təmiri</li></ul><p>10+ illik təcrübəmizlə keyfiyyətli və zəmanətli xidmət göstəririk.</p>',
                    'img_alt' => 'Boru təmiri xidməti',
                    'img_title' => 'Peşəkar boru təmiri',
                    'meta_title' => 'Boru Təmiri Xidməti | Santexnik Ustası',
                    'meta_description' => 'Bakıda peşəkar boru təmiri xidməti. Sızan borular, tıxanma, dəyişdirmə - hər problemi həll edirik.',
                    'meta_keywords' => 'boru təmiri, santexnik, boru dəyişdirmə, Bakı'
                ],
                'en' => [
                    'title' => 'Pipe Repair',
                    'slug' => 'pipe-repair',
                    'short_description' => 'Repair and replacement service for all types of pipes.',
                    'description' => '<p>Our professional pipe repair service solves all pipe problems in homes and offices.</p><h3>Our Services:</h3><ul><li>Leaking pipe repair</li><li>Old pipe replacement</li><li>Plastic and metal pipe installation</li><li>Sewer pipe repair</li></ul><p>We provide quality and guaranteed service with 10+ years of experience.</p>',
                    'img_alt' => 'Pipe repair service',
                    'img_title' => 'Professional pipe repair',
                    'meta_title' => 'Pipe Repair Service | Plumbing Master',
                    'meta_description' => 'Professional pipe repair service in Baku. Leaking pipes, blockages, replacement - we solve every problem.',
                    'meta_keywords' => 'pipe repair, plumber, pipe replacement, Baku'
                ],
                'ru' => [
                    'title' => 'Ремонт труб',
                    'slug' => 'remont-trub',
                    'short_description' => 'Ремонт и замена всех видов труб.',
                    'description' => '<p>Наша профессиональная служба ремонта труб решает все проблемы с трубами в домах и офисах.</p><h3>Наши услуги:</h3><ul><li>Ремонт протекающих труб</li><li>Замена старых труб</li><li>Установка пластиковых и металлических труб</li><li>Ремонт канализационных труб</li></ul><p>Мы предоставляем качественный и гарантированный сервис с 10+ летним опытом.</p>',
                    'img_alt' => 'Услуга ремонта труб',
                    'img_title' => 'Профессиональный ремонт труб',
                    'meta_title' => 'Ремонт труб | Мастер Сантехник',
                    'meta_description' => 'Профессиональный ремонт труб в Баку. Протечки, засоры, замена - решаем любую проблему.',
                    'meta_keywords' => 'ремонт труб, сантехник, замена труб, Баку'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=800&h=600&fit=crop',
                'icon' => 'ri-home-gear-line',
                'az' => [
                    'title' => 'Kanalizasiya Təmizliyi',
                    'slug' => 'kanalizasiya-temizliyi',
                    'short_description' => 'Kanalizasiya sisteminin təmizlənməsi və baxımı.',
                    'description' => '<p>Kanalizasiya tıxanması evdə ən çox rast gəlinən problemlərdən biridir. Peşəkar avadanlıqlarımızla bu problemi tez həll edirik.</p><h3>Təmizlik növləri:</h3><ul><li>Mətbəx boruları</li><li>Vanna və duş kanalizasiyası</li><li>Tualet tıxanması</li><li>Əsas kanalizasiya xətti</li></ul>',
                    'img_alt' => 'Kanalizasiya təmizliyi',
                    'img_title' => 'Kanalizasiya təmizlənməsi xidməti',
                    'meta_title' => 'Kanalizasiya Təmizliyi | Santexnik Ustası',
                    'meta_description' => 'Kanalizasiya tıxanması həlli. Peşəkar avadanlıqlarla sürətli təmizlik xidməti.',
                    'meta_keywords' => 'kanalizasiya təmizliyi, tıxanma, santexnik'
                ],
                'en' => [
                    'title' => 'Drain Cleaning',
                    'slug' => 'drain-cleaning',
                    'short_description' => 'Sewer system cleaning and maintenance.',
                    'description' => '<p>Sewer blockage is one of the most common problems at home. We solve this problem quickly with our professional equipment.</p><h3>Cleaning types:</h3><ul><li>Kitchen pipes</li><li>Bath and shower drain</li><li>Toilet blockage</li><li>Main sewer line</li></ul>',
                    'img_alt' => 'Drain cleaning',
                    'img_title' => 'Drain cleaning service',
                    'meta_title' => 'Drain Cleaning | Plumbing Master',
                    'meta_description' => 'Drain blockage solution. Fast cleaning service with professional equipment.',
                    'meta_keywords' => 'drain cleaning, blockage, plumber'
                ],
                'ru' => [
                    'title' => 'Чистка канализации',
                    'slug' => 'chistka-kanalizacii',
                    'short_description' => 'Чистка и обслуживание канализационной системы.',
                    'description' => '<p>Засор канализации - одна из самых распространенных проблем дома. Мы быстро решаем эту проблему с помощью нашего профессионального оборудования.</p><h3>Виды чистки:</h3><ul><li>Кухонные трубы</li><li>Слив ванны и душа</li><li>Засор унитаза</li><li>Главная канализационная линия</li></ul>',
                    'img_alt' => 'Чистка канализации',
                    'img_title' => 'Услуга чистки канализации',
                    'meta_title' => 'Чистка канализации | Мастер Сантехник',
                    'meta_description' => 'Решение засоров канализации. Быстрая чистка с профессиональным оборудованием.',
                    'meta_keywords' => 'чистка канализации, засор, сантехник'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1613685703305-a9fa3f98e0ce?w=800&h=600&fit=crop',
                'icon' => 'ri-temp-hot-line',
                'az' => [
                    'title' => 'Qızdırıcı Quraşdırılması',
                    'slug' => 'qizdirici-qurasdirilmasi',
                    'short_description' => 'Su qızdırıcılarının quraşdırılması və təmiri.',
                    'description' => '<p>Müasir su qızdırıcılarının quraşdırılması və köhnə qızdırıcıların təmiri xidməti.</p><h3>Xidmətlər:</h3><ul><li>Yeni qızdırıcı quraşdırılması</li><li>Köhnə qızdırıcı təmiri</li><li>Anbar (boiler) quraşdırılması</li><li>Ani qızdırıcı montajı</li></ul>',
                    'img_alt' => 'Qızdırıcı quraşdırılması',
                    'img_title' => 'Su qızdırıcısı montajı',
                    'meta_title' => 'Qızdırıcı Quraşdırılması | Santexnik Ustası',
                    'meta_description' => 'Su qızdırıcısının quraşdırılması və təmiri. Boiler və ani qızdırıcı montajı.',
                    'meta_keywords' => 'qızdırıcı, boiler, su qızdırıcı, montaj'
                ],
                'en' => [
                    'title' => 'Water Heater Installation',
                    'slug' => 'water-heater-installation',
                    'short_description' => 'Installation and repair of water heaters.',
                    'description' => '<p>Installation of modern water heaters and repair of old heaters.</p><h3>Services:</h3><ul><li>New heater installation</li><li>Old heater repair</li><li>Boiler installation</li><li>Instant heater mounting</li></ul>',
                    'img_alt' => 'Water heater installation',
                    'img_title' => 'Water heater mounting',
                    'meta_title' => 'Water Heater Installation | Plumbing Master',
                    'meta_description' => 'Water heater installation and repair. Boiler and instant heater mounting.',
                    'meta_keywords' => 'water heater, boiler, heater, installation'
                ],
                'ru' => [
                    'title' => 'Установка водонагревателя',
                    'slug' => 'ustanovka-vodonagrevatelya',
                    'short_description' => 'Установка и ремонт водонагревателей.',
                    'description' => '<p>Установка современных водонагревателей и ремонт старых нагревателей.</p><h3>Услуги:</h3><ul><li>Установка нового нагревателя</li><li>Ремонт старого нагревателя</li><li>Установка бойлера</li><li>Монтаж проточного нагревателя</li></ul>',
                    'img_alt' => 'Установка водонагревателя',
                    'img_title' => 'Монтаж водонагревателя',
                    'meta_title' => 'Установка водонагревателя | Мастер Сантехник',
                    'meta_description' => 'Установка и ремонт водонагревателя. Монтаж бойлера и проточного нагревателя.',
                    'meta_keywords' => 'водонагреватель, бойлер, нагреватель, установка'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?w=800&h=600&fit=crop',
                'icon' => 'ri-drop-fill',
                'az' => [
                    'title' => 'Kran Təmiri',
                    'slug' => 'kran-temiri',
                    'short_description' => 'Kranların təmiri, dəyişdirilməsi və quraşdırılması.',
                    'description' => '<p>Damlayan və ya sınmış kranların təmiri və yeni kranların quraşdırılması.</p><h3>Xidmətlər:</h3><ul><li>Damlayan kran təmiri</li><li>Kran dəyişdirilməsi</li><li>Mikser quraşdırılması</li><li>Sensor kran montajı</li></ul>',
                    'img_alt' => 'Kran təmiri',
                    'img_title' => 'Kran təmiri xidməti',
                    'meta_title' => 'Kran Təmiri | Santexnik Ustası',
                    'meta_description' => 'Damlayan kran təmiri və yeni kran quraşdırılması. Mikser montajı.',
                    'meta_keywords' => 'kran təmiri, mikser, kran dəyişmə'
                ],
                'en' => [
                    'title' => 'Faucet Repair',
                    'slug' => 'faucet-repair',
                    'short_description' => 'Faucet repair, replacement and installation.',
                    'description' => '<p>Repair of dripping or broken faucets and installation of new faucets.</p><h3>Services:</h3><ul><li>Dripping faucet repair</li><li>Faucet replacement</li><li>Mixer installation</li><li>Sensor faucet mounting</li></ul>',
                    'img_alt' => 'Faucet repair',
                    'img_title' => 'Faucet repair service',
                    'meta_title' => 'Faucet Repair | Plumbing Master',
                    'meta_description' => 'Dripping faucet repair and new faucet installation. Mixer mounting.',
                    'meta_keywords' => 'faucet repair, mixer, faucet replacement'
                ],
                'ru' => [
                    'title' => 'Ремонт крана',
                    'slug' => 'remont-krana',
                    'short_description' => 'Ремонт, замена и установка кранов.',
                    'description' => '<p>Ремонт капающих или сломанных кранов и установка новых кранов.</p><h3>Услуги:</h3><ul><li>Ремонт капающего крана</li><li>Замена крана</li><li>Установка смесителя</li><li>Монтаж сенсорного крана</li></ul>',
                    'img_alt' => 'Ремонт крана',
                    'img_title' => 'Услуга ремонта крана',
                    'meta_title' => 'Ремонт крана | Мастер Сантехник',
                    'meta_description' => 'Ремонт капающего крана и установка нового крана. Монтаж смесителя.',
                    'meta_keywords' => 'ремонт крана, смеситель, замена крана'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?w=800&h=600&fit=crop',
                'icon' => 'ri-home-4-line',
                'az' => [
                    'title' => 'Vanna Quraşdırılması',
                    'slug' => 'vanna-qurasdirilmasi',
                    'short_description' => 'Vanna və duş kabinalarının quraşdırılması.',
                    'description' => '<p>Yeni vanna və duş kabinalarının peşəkar quraşdırılması xidməti.</p><h3>Xidmətlər:</h3><ul><li>Vanna quraşdırılması</li><li>Duş kabina montajı</li><li>Jakuzi quraşdırılması</li><li>Köhnə vannasın sökülməsi</li></ul>',
                    'img_alt' => 'Vanna quraşdırılması',
                    'img_title' => 'Vanna montajı',
                    'meta_title' => 'Vanna Quraşdırılması | Santexnik Ustası',
                    'meta_description' => 'Vanna və duş kabina quraşdırılması. Jakuzi montajı.',
                    'meta_keywords' => 'vanna quraşdırılması, duş kabina, jakuzi'
                ],
                'en' => [
                    'title' => 'Bathtub Installation',
                    'slug' => 'bathtub-installation',
                    'short_description' => 'Installation of bathtubs and shower cabins.',
                    'description' => '<p>Professional installation service for new bathtubs and shower cabins.</p><h3>Services:</h3><ul><li>Bathtub installation</li><li>Shower cabin mounting</li><li>Jacuzzi installation</li><li>Old bathtub removal</li></ul>',
                    'img_alt' => 'Bathtub installation',
                    'img_title' => 'Bathtub mounting',
                    'meta_title' => 'Bathtub Installation | Plumbing Master',
                    'meta_description' => 'Bathtub and shower cabin installation. Jacuzzi mounting.',
                    'meta_keywords' => 'bathtub installation, shower cabin, jacuzzi'
                ],
                'ru' => [
                    'title' => 'Установка ванны',
                    'slug' => 'ustanovka-vanny',
                    'short_description' => 'Установка ванн и душевых кабин.',
                    'description' => '<p>Профессиональная установка новых ванн и душевых кабин.</p><h3>Услуги:</h3><ul><li>Установка ванны</li><li>Монтаж душевой кабины</li><li>Установка джакузи</li><li>Демонтаж старой ванны</li></ul>',
                    'img_alt' => 'Установка ванны',
                    'img_title' => 'Монтаж ванны',
                    'meta_title' => 'Установка ванны | Мастер Сантехник',
                    'meta_description' => 'Установка ванны и душевой кабины. Монтаж джакузи.',
                    'meta_keywords' => 'установка ванны, душевая кабина, джакузи'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1564540586988-aa4e53c3d799?w=800&h=600&fit=crop',
                'icon' => 'ri-settings-3-line',
                'az' => [
                    'title' => 'Tualet Təmiri',
                    'slug' => 'tualet-temiri',
                    'short_description' => 'Unitaz təmiri və quraşdırılması xidməti.',
                    'description' => '<p>Tualet problemlərinin həlli və yeni unitazların quraşdırılması.</p><h3>Xidmətlər:</h3><ul><li>Unitaz təmiri</li><li>Yeni unitaz quraşdırılması</li><li>Sifon təmiri</li><li>Tualet tıxanmasının açılması</li></ul>',
                    'img_alt' => 'Tualet təmiri',
                    'img_title' => 'Unitaz təmiri',
                    'meta_title' => 'Tualet Təmiri | Santexnik Ustası',
                    'meta_description' => 'Unitaz təmiri və quraşdırılması. Tualet tıxanmasının həlli.',
                    'meta_keywords' => 'tualet təmiri, unitaz, sifon təmiri'
                ],
                'en' => [
                    'title' => 'Toilet Repair',
                    'slug' => 'toilet-repair',
                    'short_description' => 'Toilet repair and installation service.',
                    'description' => '<p>Solving toilet problems and installing new toilets.</p><h3>Services:</h3><ul><li>Toilet repair</li><li>New toilet installation</li><li>Cistern repair</li><li>Toilet unclogging</li></ul>',
                    'img_alt' => 'Toilet repair',
                    'img_title' => 'Toilet repair',
                    'meta_title' => 'Toilet Repair | Plumbing Master',
                    'meta_description' => 'Toilet repair and installation. Toilet unclogging solution.',
                    'meta_keywords' => 'toilet repair, toilet, cistern repair'
                ],
                'ru' => [
                    'title' => 'Ремонт унитаза',
                    'slug' => 'remont-unitaza',
                    'short_description' => 'Ремонт и установка унитаза.',
                    'description' => '<p>Решение проблем с туалетом и установка новых унитазов.</p><h3>Услуги:</h3><ul><li>Ремонт унитаза</li><li>Установка нового унитаза</li><li>Ремонт бачка</li><li>Прочистка унитаза</li></ul>',
                    'img_alt' => 'Ремонт унитаза',
                    'img_title' => 'Ремонт унитаза',
                    'meta_title' => 'Ремонт унитаза | Мастер Сантехник',
                    'meta_description' => 'Ремонт и установка унитаза. Прочистка унитаза.',
                    'meta_keywords' => 'ремонт унитаза, унитаз, ремонт бачка'
                ],
            ],
        ];

        foreach ($services as $index => $data) {
            $imagePath = $this->downloadImage(
                $data['image_url'],
                'services',
                'service-' . ($index + 1) . '.jpg'
            );

            Service::create([
                'image' => $imagePath,
                'icon' => $data['icon'],
                'is_active' => true,
                'is_featured' => $index < 3,
                'order' => $index + 1,
                'az' => $data['az'],
                'en' => $data['en'],
                'ru' => $data['ru'],
            ]);
        }
    }

    private function seedPortfolios()
    {
        $services = Service::all();

        $portfolios = [
            [
                'image_url' => 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=800&h=600&fit=crop',
                'gallery' => [
                    'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1607472586893-edb57bdc0e39?w=800&h=600&fit=crop',
                ],
                'az' => [
                    'title' => 'Mənzildə tam santexnik işləri',
                    'slug' => 'menzilde-tam-santexnik-isleri',
                    'short_description' => 'Yeni mənzildə tam santexnik sisteminin qurulması.',
                    'description' => '<p>Bu layihədə yeni inşa edilmiş mənzildə bütün santexnik sistemini sıfırdan qurduq.</p><p>Boruların çəkilməsi, vannaxana avadanlıqlarının quraşdırılması, qızdırıcı sistemi - hamısı peşəkar şəkildə yerinə yetirildi.</p>',
                    'img_alt' => 'Mənzil santexnik işləri',
                    'img_title' => 'Tam santexnik sistemi',
                    'meta_title' => 'Mənzildə Santexnik İşləri | Portfolio',
                    'meta_description' => 'Yeni mənzildə tam santexnik sisteminin qurulması layihəsi.'
                ],
                'en' => [
                    'title' => 'Complete apartment plumbing',
                    'slug' => 'complete-apartment-plumbing',
                    'short_description' => 'Complete plumbing system installation in new apartment.',
                    'description' => '<p>In this project, we built the entire plumbing system from scratch in a newly constructed apartment.</p><p>Pipe installation, bathroom equipment installation, heating system - everything was done professionally.</p>',
                    'img_alt' => 'Apartment plumbing work',
                    'img_title' => 'Complete plumbing system',
                    'meta_title' => 'Apartment Plumbing Works | Portfolio',
                    'meta_description' => 'Complete plumbing system installation project in new apartment.'
                ],
                'ru' => [
                    'title' => 'Полная сантехника в квартире',
                    'slug' => 'polnaya-santehnika-v-kvartire',
                    'short_description' => 'Установка полной сантехнической системы в новой квартире.',
                    'description' => '<p>В этом проекте мы построили всю сантехническую систему с нуля в новой квартире.</p><p>Прокладка труб, установка сантехники, система отопления - все сделано профессионально.</p>',
                    'img_alt' => 'Сантехнические работы в квартире',
                    'img_title' => 'Полная сантехническая система',
                    'meta_title' => 'Сантехника в квартире | Портфолио',
                    'meta_description' => 'Проект установки полной сантехнической системы в новой квартире.'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=800&h=600&fit=crop',
                'gallery' => [
                    'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?w=800&h=600&fit=crop',
                ],
                'az' => [
                    'title' => 'Vannaxana renovasiyası',
                    'slug' => 'vannaxana-renovasiyasi',
                    'short_description' => 'Köhnə vannaxananın tam yenilənməsi.',
                    'description' => '<p>Müştərimizin köhnə vannaxanasını tamamilə yeniləyərək modern dizayna çevirdik.</p><p>Yeni vanna, duş kabina, unitaz və bütün santexnik avadanlıqlar quraşdırıldı.</p>',
                    'img_alt' => 'Vannaxana renovasiyası',
                    'img_title' => 'Modern vannaxana',
                    'meta_title' => 'Vannaxana Renovasiyası | Portfolio',
                    'meta_description' => 'Köhnə vannaxananın tam yenilənməsi və modernləşdirilməsi.'
                ],
                'en' => [
                    'title' => 'Bathroom renovation',
                    'slug' => 'bathroom-renovation',
                    'short_description' => 'Complete renovation of old bathroom.',
                    'description' => '<p>We completely renovated our customer\'s old bathroom and turned it into a modern design.</p><p>New bathtub, shower cabin, toilet and all plumbing equipment were installed.</p>',
                    'img_alt' => 'Bathroom renovation',
                    'img_title' => 'Modern bathroom',
                    'meta_title' => 'Bathroom Renovation | Portfolio',
                    'meta_description' => 'Complete renovation and modernization of old bathroom.'
                ],
                'ru' => [
                    'title' => 'Ремонт ванной комнаты',
                    'slug' => 'remont-vannoj-komnaty',
                    'short_description' => 'Полный ремонт старой ванной комнаты.',
                    'description' => '<p>Мы полностью отремонтировали старую ванную комнату нашего клиента и превратили её в современный дизайн.</p><p>Установлены новая ванна, душевая кабина, унитаз и вся сантехника.</p>',
                    'img_alt' => 'Ремонт ванной комнаты',
                    'img_title' => 'Современная ванная',
                    'meta_title' => 'Ремонт ванной комнаты | Портфолио',
                    'meta_description' => 'Полный ремонт и модернизация старой ванной комнаты.'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1613685703305-a9fa3f98e0ce?w=800&h=600&fit=crop',
                'gallery' => [
                    'https://images.unsplash.com/photo-1613685703305-a9fa3f98e0ce?w=800&h=600&fit=crop',
                ],
                'az' => [
                    'title' => 'Ofis binasında santexnik sistemi',
                    'slug' => 'ofis-binasinda-santexnik-sistemi',
                    'short_description' => 'Böyük ofis binasında santexnik sisteminin qurulması.',
                    'description' => '<p>5 mərtəbəli ofis binasında tam santexnik sistemini qurduq.</p><p>Hər mərtəbədə tualet, mətbəx və su təchizatı sistemi peşəkar şəkildə icra edildi.</p>',
                    'img_alt' => 'Ofis santexnik sistemi',
                    'img_title' => 'Kommersiya santexnik işləri',
                    'meta_title' => 'Ofis Binası Santexnik | Portfolio',
                    'meta_description' => 'Böyük ofis binasında santexnik sisteminin qurulması layihəsi.'
                ],
                'en' => [
                    'title' => 'Office building plumbing system',
                    'slug' => 'office-building-plumbing-system',
                    'short_description' => 'Plumbing system installation in large office building.',
                    'description' => '<p>We installed a complete plumbing system in a 5-story office building.</p><p>Toilet, kitchen and water supply system on each floor were professionally executed.</p>',
                    'img_alt' => 'Office plumbing system',
                    'img_title' => 'Commercial plumbing work',
                    'meta_title' => 'Office Building Plumbing | Portfolio',
                    'meta_description' => 'Plumbing system installation project in large office building.'
                ],
                'ru' => [
                    'title' => 'Сантехника в офисном здании',
                    'slug' => 'santehnika-v-ofisnom-zdanii',
                    'short_description' => 'Установка сантехнической системы в большом офисном здании.',
                    'description' => '<p>Мы установили полную сантехническую систему в 5-этажном офисном здании.</p><p>Туалет, кухня и система водоснабжения на каждом этаже были профессионально выполнены.</p>',
                    'img_alt' => 'Офисная сантехника',
                    'img_title' => 'Коммерческие сантехнические работы',
                    'meta_title' => 'Сантехника офисного здания | Портфолио',
                    'meta_description' => 'Проект установки сантехнической системы в большом офисном здании.'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?w=800&h=600&fit=crop',
                'gallery' => [
                    'https://images.unsplash.com/photo-1620626011761-996317b8d101?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1564540586988-aa4e53c3d799?w=800&h=600&fit=crop',
                ],
                'az' => [
                    'title' => 'Villada istilik sistemi',
                    'slug' => 'villada-istilik-sistemi',
                    'short_description' => 'Lüks villada mərkəzi istilik sisteminin qurulması.',
                    'description' => '<p>Mərdəkan qəsəbəsindəki lüks villada tam istilik sistemini qurduq.</p><p>Qaz qazanı, radiatorlar və yeraltı istilik sistemi quraşdırıldı.</p>',
                    'img_alt' => 'Villa istilik sistemi',
                    'img_title' => 'Mərkəzi istilik',
                    'meta_title' => 'Villa İstilik Sistemi | Portfolio',
                    'meta_description' => 'Lüks villada mərkəzi istilik sisteminin qurulması.'
                ],
                'en' => [
                    'title' => 'Villa heating system',
                    'slug' => 'villa-heating-system',
                    'short_description' => 'Central heating system installation in luxury villa.',
                    'description' => '<p>We installed a complete heating system in a luxury villa in Mardakan.</p><p>Gas boiler, radiators and underfloor heating system were installed.</p>',
                    'img_alt' => 'Villa heating system',
                    'img_title' => 'Central heating',
                    'meta_title' => 'Villa Heating System | Portfolio',
                    'meta_description' => 'Central heating system installation in luxury villa.'
                ],
                'ru' => [
                    'title' => 'Система отопления виллы',
                    'slug' => 'sistema-otopleniya-villy',
                    'short_description' => 'Установка центрального отопления в роскошной вилле.',
                    'description' => '<p>Мы установили полную систему отопления в роскошной вилле в Мардакане.</p><p>Установлены газовый котел, радиаторы и система теплого пола.</p>',
                    'img_alt' => 'Система отопления виллы',
                    'img_title' => 'Центральное отопление',
                    'meta_title' => 'Система отопления виллы | Портфолио',
                    'meta_description' => 'Установка центрального отопления в роскошной вилле.'
                ],
            ],
        ];

        foreach ($portfolios as $index => $data) {
            $imagePath = $this->downloadImage(
                $data['image_url'],
                'portfolios',
                'portfolio-' . ($index + 1) . '.jpg'
            );

            $portfolio = Portfolio::create([
                'service_id' => $services->isNotEmpty() ? $services->random()->id : null,
                'image' => $imagePath,
                'is_active' => true,
                'is_featured' => $index < 2,
                'order' => $index + 1,
                'az' => $data['az'],
                'en' => $data['en'],
                'ru' => $data['ru'],
            ]);

            // Add gallery images
            foreach ($data['gallery'] as $gi => $galleryUrl) {
                $galleryPath = $this->downloadImage(
                    $galleryUrl,
                    'portfolios/gallery',
                    'portfolio-' . ($index + 1) . '-gallery-' . ($gi + 1) . '.jpg'
                );
                if ($galleryPath) {
                    PortfolioImage::create([
                        'portfolio_id' => $portfolio->id,
                        'image' => $galleryPath,
                        'order' => $gi + 1,
                    ]);
                }
            }
        }
    }

    private function seedBlogs()
    {
        // First create tags
        $tagsData = [
            ['is_active' => true, 'az' => ['title' => 'Santexnik', 'slug' => 'santexnik'], 'en' => ['title' => 'Plumbing', 'slug' => 'plumbing'], 'ru' => ['title' => 'Сантехника', 'slug' => 'santehnika']],
            ['is_active' => true, 'az' => ['title' => 'Məsləhət', 'slug' => 'meslehet'], 'en' => ['title' => 'Tips', 'slug' => 'tips'], 'ru' => ['title' => 'Советы', 'slug' => 'sovety']],
            ['is_active' => true, 'az' => ['title' => 'Təmir', 'slug' => 'temir'], 'en' => ['title' => 'Repair', 'slug' => 'repair'], 'ru' => ['title' => 'Ремонт', 'slug' => 'remont']],
        ];

        foreach ($tagsData as $tagData) {
            Tag::create($tagData);
        }

        $tags = Tag::all();
        $services = Service::all();

        $blogs = [
            [
                'image_url' => 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=800&h=500&fit=crop',
                'az' => [
                    'title' => 'Evdə boru sızmasının qarşısını necə almaq olar?',
                    'slug' => 'evde-boru-sizmasinin-qarsisini-nece-almaq-olar',
                    'short_description' => 'Boru sızmasının əsas səbəbləri və profilaktik tədbirlər haqqında məlumat.',
                    'description' => '<p>Boru sızması evdə ən çox rast gəlinən problemlərdən biridir. Bu məqalədə boru sızmasının əsas səbəblərini və onun qarşısını almaq üçün görülə biləcək tədbirləri araşdıracağıq.</p><h3>Sızmanın əsas səbəbləri</h3><ul><li>Boruların köhnəlməsi</li><li>Yüksək su təzyiqi</li><li>Temperatur dəyişiklikləri</li><li>Korroziya</li></ul><h3>Profilaktik tədbirlər</h3><p>Mütəmadi yoxlama aparın, su təzyiqini nizamlayın və köhnə boruları vaxtında dəyişdirin.</p>',
                    'img_alt' => 'Boru sızması',
                    'img_title' => 'Boru sızmasının qarşısının alınması',
                    'meta_title' => 'Boru Sızmasının Qarşısını Almaq | Blog',
                    'meta_description' => 'Evdə boru sızmasının səbəbləri və qarşısının alınması üsulları.',
                    'meta_keywords' => 'boru sızması, santexnik, profilaktika'
                ],
                'en' => [
                    'title' => 'How to prevent pipe leaks at home?',
                    'slug' => 'how-to-prevent-pipe-leaks-at-home',
                    'short_description' => 'Information about main causes of pipe leaks and preventive measures.',
                    'description' => '<p>Pipe leakage is one of the most common problems at home. In this article, we will explore the main causes of pipe leaks and measures that can be taken to prevent them.</p><h3>Main causes of leaks</h3><ul><li>Aging pipes</li><li>High water pressure</li><li>Temperature changes</li><li>Corrosion</li></ul><h3>Preventive measures</h3><p>Conduct regular inspections, regulate water pressure and replace old pipes in time.</p>',
                    'img_alt' => 'Pipe leak',
                    'img_title' => 'Preventing pipe leaks',
                    'meta_title' => 'Preventing Pipe Leaks | Blog',
                    'meta_description' => 'Causes of pipe leaks at home and ways to prevent them.',
                    'meta_keywords' => 'pipe leak, plumbing, prevention'
                ],
                'ru' => [
                    'title' => 'Как предотвратить протечку труб дома?',
                    'slug' => 'kak-predotvratit-protechku-trub-doma',
                    'short_description' => 'Информация об основных причинах протечки труб и профилактических мерах.',
                    'description' => '<p>Протечка труб - одна из самых распространенных проблем дома. В этой статье мы рассмотрим основные причины протечки труб и меры по их предотвращению.</p><h3>Основные причины протечек</h3><ul><li>Старение труб</li><li>Высокое давление воды</li><li>Перепады температуры</li><li>Коррозия</li></ul><h3>Профилактические меры</h3><p>Проводите регулярные осмотры, регулируйте давление воды и вовремя меняйте старые трубы.</p>',
                    'img_alt' => 'Протечка труб',
                    'img_title' => 'Предотвращение протечки труб',
                    'meta_title' => 'Предотвращение протечки труб | Блог',
                    'meta_description' => 'Причины протечки труб дома и способы их предотвращения.',
                    'meta_keywords' => 'протечка труб, сантехника, профилактика'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1613685703305-a9fa3f98e0ce?w=800&h=500&fit=crop',
                'az' => [
                    'title' => 'Su qızdırıcısı seçərkən nələrə diqqət etməli?',
                    'slug' => 'su-qizdiricisi-secerken-nelere-diqqet-etmeli',
                    'short_description' => 'Doğru su qızdırıcısı seçimi üçün vacib məqamlar.',
                    'description' => '<p>Su qızdırıcısı seçimi ailənizin ehtiyaclarına və büdcənizə uyğun olmalıdır.</p><h3>Nəzərə alınmalı amillər</h3><ul><li>Tutum - ailə üzvlərinin sayına görə</li><li>Enerji effektivliyi</li><li>Qızdırma tipi - ani və ya anbar</li><li>Marka və zəmanət</li></ul>',
                    'img_alt' => 'Su qızdırıcısı',
                    'img_title' => 'Su qızdırıcısı seçimi',
                    'meta_title' => 'Su Qızdırıcısı Seçimi | Blog',
                    'meta_description' => 'Su qızdırıcısı seçərkən diqqət edilməli məqamlar.',
                    'meta_keywords' => 'su qızdırıcı, boiler, seçim'
                ],
                'en' => [
                    'title' => 'What to consider when choosing a water heater?',
                    'slug' => 'what-to-consider-when-choosing-water-heater',
                    'short_description' => 'Important points for choosing the right water heater.',
                    'description' => '<p>Choosing a water heater should match your family\'s needs and budget.</p><h3>Factors to consider</h3><ul><li>Capacity - according to number of family members</li><li>Energy efficiency</li><li>Heating type - instant or tank</li><li>Brand and warranty</li></ul>',
                    'img_alt' => 'Water heater',
                    'img_title' => 'Water heater selection',
                    'meta_title' => 'Water Heater Selection | Blog',
                    'meta_description' => 'Points to consider when choosing a water heater.',
                    'meta_keywords' => 'water heater, boiler, selection'
                ],
                'ru' => [
                    'title' => 'На что обратить внимание при выборе водонагревателя?',
                    'slug' => 'na-chto-obratit-vnimanie-pri-vybore-vodonagrevatelya',
                    'short_description' => 'Важные моменты при выборе правильного водонагревателя.',
                    'description' => '<p>Выбор водонагревателя должен соответствовать потребностям вашей семьи и бюджету.</p><h3>Факторы, которые следует учитывать</h3><ul><li>Емкость - по количеству членов семьи</li><li>Энергоэффективность</li><li>Тип нагрева - проточный или накопительный</li><li>Бренд и гарантия</li></ul>',
                    'img_alt' => 'Водонагреватель',
                    'img_title' => 'Выбор водонагревателя',
                    'meta_title' => 'Выбор водонагревателя | Блог',
                    'meta_description' => 'На что обратить внимание при выборе водонагревателя.',
                    'meta_keywords' => 'водонагреватель, бойлер, выбор'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?w=800&h=500&fit=crop',
                'az' => [
                    'title' => 'Kranın damlamaması üçün sadə həll yolları',
                    'slug' => 'kranin-damlamamasi-ucun-sade-hell-yollari',
                    'short_description' => 'Damlayan kranı özünüz necə təmir edə bilərsiniz?',
                    'description' => '<p>Damlayan kran həm su itkisinə, həm də yuxusuzluğa səbəb ola bilər. Bəzi hallarda bu problemi özünüz həll edə bilərsiniz.</p><h3>Sadə həll yolları</h3><ul><li>Rezin contanı dəyişdirin</li><li>Kranın vidalarını sıxın</li><li>Kartric dəyişdirin</li></ul><p>Əgər problem davam edərsə, peşəkar santexnik çağırmaq lazımdır.</p>',
                    'img_alt' => 'Kran təmiri',
                    'img_title' => 'Damlayan kranın təmiri',
                    'meta_title' => 'Damlayan Kran Təmiri | Blog',
                    'meta_description' => 'Damlayan kranı özünüz necə təmir edə bilərsiniz.',
                    'meta_keywords' => 'kran təmiri, damlayan kran, DIY'
                ],
                'en' => [
                    'title' => 'Simple solutions to stop a dripping faucet',
                    'slug' => 'simple-solutions-to-stop-dripping-faucet',
                    'short_description' => 'How can you fix a dripping faucet yourself?',
                    'description' => '<p>A dripping faucet can cause both water loss and sleeplessness. In some cases, you can solve this problem yourself.</p><h3>Simple solutions</h3><ul><li>Replace the rubber gasket</li><li>Tighten the faucet screws</li><li>Replace the cartridge</li></ul><p>If the problem persists, you need to call a professional plumber.</p>',
                    'img_alt' => 'Faucet repair',
                    'img_title' => 'Fixing dripping faucet',
                    'meta_title' => 'Dripping Faucet Repair | Blog',
                    'meta_description' => 'How can you fix a dripping faucet yourself.',
                    'meta_keywords' => 'faucet repair, dripping faucet, DIY'
                ],
                'ru' => [
                    'title' => 'Простые решения для устранения капающего крана',
                    'slug' => 'prostye-resheniya-dlya-ustraneniya-kapayushchego-krana',
                    'short_description' => 'Как самостоятельно починить капающий кран?',
                    'description' => '<p>Капающий кран может вызвать как потерю воды, так и бессонницу. В некоторых случаях вы можете решить эту проблему самостоятельно.</p><h3>Простые решения</h3><ul><li>Замените резиновую прокладку</li><li>Затяните винты крана</li><li>Замените картридж</li></ul><p>Если проблема не устранена, необходимо вызвать профессионального сантехника.</p>',
                    'img_alt' => 'Ремонт крана',
                    'img_title' => 'Устранение капающего крана',
                    'meta_title' => 'Ремонт капающего крана | Блог',
                    'meta_description' => 'Как самостоятельно починить капающий кран.',
                    'meta_keywords' => 'ремонт крана, капающий кран, DIY'
                ],
            ],
        ];

        foreach ($blogs as $index => $data) {
            $imagePath = $this->downloadImage(
                $data['image_url'],
                'blogs',
                'blog-' . ($index + 1) . '.jpg'
            );

            $blog = Blog::create([
                'service_id' => $services->isNotEmpty() ? $services->random()->id : null,
                'image' => $imagePath,
                'is_active' => true,
                'is_featured' => $index < 2,
                'view' => rand(50, 500),
                'az' => $data['az'],
                'en' => $data['en'],
                'ru' => $data['ru'],
            ]);

            // Attach random tags
            if ($tags->isNotEmpty()) {
                $blog->tags()->attach($tags->random(rand(1, 2))->pluck('id')->toArray());
            }
        }
    }

    private function seedFaqs()
    {
        $faqs = [
            [
                'az' => [
                    'question' => 'Təcili santexnik xidməti mövcuddurmu?',
                    'answer' => 'Bəli, 24/7 təcili santexnik xidməti göstəririk. Gecə-gündüz istənilən vaxt zəng edə bilərsiniz.'
                ],
                'en' => [
                    'question' => 'Is emergency plumbing service available?',
                    'answer' => 'Yes, we provide 24/7 emergency plumbing service. You can call us at any time of day or night.'
                ],
                'ru' => [
                    'question' => 'Доступна ли экстренная сантехническая служба?',
                    'answer' => 'Да, мы предоставляем экстренную сантехническую службу 24/7. Вы можете позвонить нам в любое время дня и ночи.'
                ],
            ],
            [
                'az' => [
                    'question' => 'Xidmət haqqı necə hesablanır?',
                    'answer' => 'Xidmət haqqı işin mürəkkəbliyinə və istifadə olunan materiallara görə hesablanır. Əvvəlcə pulsuz baxış keçirilir və qiymət təklifi verilir.'
                ],
                'en' => [
                    'question' => 'How is the service fee calculated?',
                    'answer' => 'The service fee is calculated based on the complexity of the work and materials used. A free inspection is conducted first and a price quote is given.'
                ],
                'ru' => [
                    'question' => 'Как рассчитывается стоимость услуги?',
                    'answer' => 'Стоимость услуги рассчитывается в зависимости от сложности работы и используемых материалов. Сначала проводится бесплатный осмотр и дается ценовое предложение.'
                ],
            ],
            [
                'az' => [
                    'question' => 'İşlərə zəmanət verilirmi?',
                    'answer' => 'Bəli, bütün işlərimizə 1 il zəmanət veririk. Zəmanət müddətində yaranan problemlər pulsuz həll edilir.'
                ],
                'en' => [
                    'question' => 'Is there a warranty for the work?',
                    'answer' => 'Yes, we provide a 1-year warranty for all our work. Problems that arise during the warranty period are solved free of charge.'
                ],
                'ru' => [
                    'question' => 'Предоставляется ли гарантия на работу?',
                    'answer' => 'Да, мы предоставляем 1-летнюю гарантию на все наши работы. Проблемы, возникающие в гарантийный период, решаются бесплатно.'
                ],
            ],
            [
                'az' => [
                    'question' => 'Hansı ərazilərdə xidmət göstərirsiniz?',
                    'answer' => 'Bakı şəhəri və Abşeron yarımadasında xidmət göstəririk. Sumqayıt, Xırdalan və digər yaxın ərazilərə də gəlirik.'
                ],
                'en' => [
                    'question' => 'Which areas do you serve?',
                    'answer' => 'We serve in Baku city and Absheron peninsula. We also come to Sumgait, Khirdalan and other nearby areas.'
                ],
                'ru' => [
                    'question' => 'В каких районах вы обслуживаете?',
                    'answer' => 'Мы обслуживаем в Баку и на Абшеронском полуострове. Мы также приезжаем в Сумгаит, Хырдалан и другие близлежащие районы.'
                ],
            ],
            [
                'az' => [
                    'question' => 'Material özümüz təmin etməliyik?',
                    'answer' => 'Xeyr, lazım olan bütün materialları biz təmin edirik. İstəsəniz özünüz də material ala bilərsiniz, bu halda yalnız iş haqqı ödənilir.'
                ],
                'en' => [
                    'question' => 'Do we need to provide materials ourselves?',
                    'answer' => 'No, we provide all necessary materials. If you wish, you can also buy materials yourself, in which case only the labor fee is paid.'
                ],
                'ru' => [
                    'question' => 'Нужно ли нам самим предоставлять материалы?',
                    'answer' => 'Нет, мы предоставляем все необходимые материалы. При желании вы можете купить материалы самостоятельно, в этом случае оплачивается только стоимость работы.'
                ],
            ],
        ];

        foreach ($faqs as $index => $data) {
            Faq::create([
                'is_active' => true,
                'order' => $index + 1,
                'az' => $data['az'],
                'en' => $data['en'],
                'ru' => $data['ru'],
            ]);
        }
    }

    private function seedReviews()
    {
        $reviews = [
            [
                'image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop&crop=face',
                'rating' => 5,
                'az' => [
                    'name' => 'Əli Məmmədov',
                    'position' => 'Ev sahibi',
                    'content' => 'Çox peşəkar və sürətli xidmət. Gecə yarısı zəng etdim, 30 dəqiqəyə gəldilər və problemi həll etdilər. Tövsiyə edirəm!'
                ],
                'en' => [
                    'name' => 'Ali Mammadov',
                    'position' => 'Homeowner',
                    'content' => 'Very professional and fast service. I called at midnight, they came in 30 minutes and solved the problem. Highly recommend!'
                ],
                'ru' => [
                    'name' => 'Али Мамедов',
                    'position' => 'Домовладелец',
                    'content' => 'Очень профессиональный и быстрый сервис. Позвонил в полночь, приехали за 30 минут и решили проблему. Рекомендую!'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop&crop=face',
                'rating' => 5,
                'az' => [
                    'name' => 'Aysel Hüseynova',
                    'position' => 'Mənzil sahibi',
                    'content' => 'Vannaxananı tamamilə yeniləməyə kömək etdilər. İş keyfiyyəti əla, qiymət münasibdir. Çox razı qaldım.'
                ],
                'en' => [
                    'name' => 'Aysel Huseynova',
                    'position' => 'Apartment owner',
                    'content' => 'They helped completely renovate the bathroom. Work quality is excellent, price is reasonable. Very satisfied.'
                ],
                'ru' => [
                    'name' => 'Айсель Гусейнова',
                    'position' => 'Владелец квартиры',
                    'content' => 'Помогли полностью отремонтировать ванную комнату. Качество работы отличное, цена разумная. Очень довольна.'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=200&h=200&fit=crop&crop=face',
                'rating' => 5,
                'az' => [
                    'name' => 'Rəşad Quliyev',
                    'position' => 'Ofis meneceri',
                    'content' => 'Ofisimizdə bütün santexnik işlərini onlar görür. Hər zaman vaxtında gəlirlər və işi keyfiyyətlə görürlər.'
                ],
                'en' => [
                    'name' => 'Rashad Guliyev',
                    'position' => 'Office manager',
                    'content' => 'They do all the plumbing work in our office. They always arrive on time and do quality work.'
                ],
                'ru' => [
                    'name' => 'Рашад Гулиев',
                    'position' => 'Офис-менеджер',
                    'content' => 'Они выполняют все сантехнические работы в нашем офисе. Всегда приезжают вовремя и делают качественную работу.'
                ],
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=200&h=200&fit=crop&crop=face',
                'rating' => 4,
                'az' => [
                    'name' => 'Leyla Əliyeva',
                    'position' => 'Villa sahibi',
                    'content' => 'Villamızda istilik sistemini qurdular. Əla iş gördülər, amma bir az uzun çəkdi. Ümumiyyətlə razıyam.'
                ],
                'en' => [
                    'name' => 'Leyla Aliyeva',
                    'position' => 'Villa owner',
                    'content' => 'They installed the heating system in our villa. Did excellent work, but took a bit long. Generally satisfied.'
                ],
                'ru' => [
                    'name' => 'Лейла Алиева',
                    'position' => 'Владелец виллы',
                    'content' => 'Установили систему отопления в нашей вилле. Сделали отличную работу, но заняло немного времени. В целом довольна.'
                ],
            ],
        ];

        foreach ($reviews as $index => $data) {
            $imagePath = $this->downloadImage(
                $data['image_url'],
                'reviews',
                'review-' . ($index + 1) . '.jpg'
            );

            Review::create([
                'image' => $imagePath,
                'rating' => $data['rating'],
                'is_active' => true,
                'order' => $index + 1,
                'az' => $data['az'],
                'en' => $data['en'],
                'ru' => $data['ru'],
            ]);
        }
    }

    private function seedAbout()
    {
        $abouts = [
            [
                'key' => 'main',
                'image_url' => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=800&h=600&fit=crop',
                'az' => [
                    'title' => 'Haqqımızda',
                    'description' => '<p>10 ildən artıq təcrübəyə malik peşəkar santexnik komandamızla Bakı və Abşeron yarımadasında keyfiyyətli xidmət göstəririk.</p><p>Müasir avadanlıqlar və təcrübəli ustalarımızla hər növ santexnik problemini həll edirik. Müştəri məmnuniyyəti bizim üçün hər şeydən önəmlidir.</p><ul><li>10+ il təcrübə</li><li>500+ razı müştəri</li><li>24/7 xidmət</li><li>1 il zəmanət</li></ul>'
                ],
                'en' => [
                    'title' => 'About Us',
                    'description' => '<p>We provide quality service in Baku and Absheron peninsula with our professional plumbing team with more than 10 years of experience.</p><p>We solve all types of plumbing problems with modern equipment and experienced craftsmen. Customer satisfaction is our top priority.</p><ul><li>10+ years of experience</li><li>500+ satisfied customers</li><li>24/7 service</li><li>1 year warranty</li></ul>'
                ],
                'ru' => [
                    'title' => 'О нас',
                    'description' => '<p>Мы предоставляем качественные услуги в Баку и на Абшеронском полуострове с нашей профессиональной сантехнической командой с более чем 10-летним опытом.</p><p>Мы решаем все виды сантехнических проблем с помощью современного оборудования и опытных мастеров. Удовлетворенность клиентов - наш главный приоритет.</p><ul><li>10+ лет опыта</li><li>500+ довольных клиентов</li><li>Сервис 24/7</li><li>Гарантия 1 год</li></ul>'
                ],
            ],
        ];

        foreach ($abouts as $data) {
            $imagePath = $this->downloadImage(
                $data['image_url'],
                'about',
                $data['key'] . '.jpg'
            );

            About::create([
                'key' => $data['key'],
                'image' => $imagePath,
                'az' => $data['az'],
                'en' => $data['en'],
                'ru' => $data['ru'],
            ]);
        }
    }

    private function seedWhyUs()
    {
        $whyUs = [
            [
                'icon' => 'ri-time-line',
                'az' => [
                    'title' => 'Sürətli Xidmət',
                    'description' => '24/7 əlçatan və 30 dəqiqə içində yerinizdə olan təcili xidmət.'
                ],
                'en' => [
                    'title' => 'Fast Service',
                    'description' => '24/7 available emergency service that reaches you within 30 minutes.'
                ],
                'ru' => [
                    'title' => 'Быстрый сервис',
                    'description' => 'Круглосуточная экстренная служба, которая доберется до вас за 30 минут.'
                ],
            ],
            [
                'icon' => 'ri-shield-check-line',
                'az' => [
                    'title' => 'Zəmanətli İş',
                    'description' => 'Bütün işlərimizə 1 il zəmanət veririk. Keyfiyyətə güvənc.'
                ],
                'en' => [
                    'title' => 'Guaranteed Work',
                    'description' => 'We provide 1 year warranty for all our work. Trust in quality.'
                ],
                'ru' => [
                    'title' => 'Гарантированная работа',
                    'description' => 'Мы даем 1 год гарантии на все наши работы. Доверие к качеству.'
                ],
            ],
            [
                'icon' => 'ri-user-star-line',
                'az' => [
                    'title' => 'Təcrübəli Ustalar',
                    'description' => '10+ il təcrübəyə malik peşəkar və sertifikatlı ustalar.'
                ],
                'en' => [
                    'title' => 'Experienced Craftsmen',
                    'description' => 'Professional and certified craftsmen with 10+ years of experience.'
                ],
                'ru' => [
                    'title' => 'Опытные мастера',
                    'description' => 'Профессиональные и сертифицированные мастера с 10+ летним опытом.'
                ],
            ],
            [
                'icon' => 'ri-money-dollar-circle-line',
                'az' => [
                    'title' => 'Münasib Qiymət',
                    'description' => 'Rəqabətli qiymətlər və pulsuz baxış xidməti.'
                ],
                'en' => [
                    'title' => 'Affordable Price',
                    'description' => 'Competitive prices and free inspection service.'
                ],
                'ru' => [
                    'title' => 'Доступная цена',
                    'description' => 'Конкурентные цены и бесплатный осмотр.'
                ],
            ],
        ];

        foreach ($whyUs as $index => $data) {
            WhyUs::create([
                'icon' => $data['icon'],
                'is_active' => true,
                'order' => $index + 1,
                'az' => $data['az'],
                'en' => $data['en'],
                'ru' => $data['ru'],
            ]);
        }
    }

    private function seedContactItems()
    {
        $items = [
            [
                'icon' => 'ri-phone-line',
                'link' => 'tel:+994501234567',
                'is_active' => true,
                'az' => ['title' => 'Telefon', 'value' => '+994 50 123 45 67'],
                'en' => ['title' => 'Phone', 'value' => '+994 50 123 45 67'],
                'ru' => ['title' => 'Телефон', 'value' => '+994 50 123 45 67'],
            ],
            [
                'icon' => 'ri-mail-line',
                'link' => 'mailto:info@santexnik.az',
                'is_active' => true,
                'az' => ['title' => 'E-poçt', 'value' => 'info@santexnik.az'],
                'en' => ['title' => 'Email', 'value' => 'info@santexnik.az'],
                'ru' => ['title' => 'Эл. почта', 'value' => 'info@santexnik.az'],
            ],
            [
                'icon' => 'ri-map-pin-line',
                'link' => 'https://maps.google.com',
                'is_active' => true,
                'az' => ['title' => 'Ünvan', 'value' => 'Bakı şəhəri, Nəsimi rayonu'],
                'en' => ['title' => 'Address', 'value' => 'Baku city, Nasimi district'],
                'ru' => ['title' => 'Адрес', 'value' => 'Город Баку, Насиминский район'],
            ],
            [
                'icon' => 'ri-time-line',
                'link' => null,
                'is_active' => true,
                'az' => ['title' => 'İş saatları', 'value' => 'Hər gün: 24/7'],
                'en' => ['title' => 'Working hours', 'value' => 'Every day: 24/7'],
                'ru' => ['title' => 'Рабочие часы', 'value' => 'Ежедневно: 24/7'],
            ],
        ];

        foreach ($items as $index => $data) {
            ContactItem::create([
                'icon' => $data['icon'],
                'link' => $data['link'],
                'is_active' => $data['is_active'],
                'order' => $index + 1,
                'az' => $data['az'],
                'en' => $data['en'],
                'ru' => $data['ru'],
            ]);
        }
    }

    private function seedSocials()
    {
        $socials = [
            ['title' => 'Facebook', 'icon' => 'ri-facebook-fill', 'link' => 'https://facebook.com/santexnik', 'is_active' => true, 'order' => 1],
            ['title' => 'Instagram', 'icon' => 'ri-instagram-line', 'link' => 'https://instagram.com/santexnik', 'is_active' => true, 'order' => 2],
            ['title' => 'WhatsApp', 'icon' => 'ri-whatsapp-line', 'link' => 'https://wa.me/994501234567', 'is_active' => true, 'order' => 3],
            ['title' => 'YouTube', 'icon' => 'ri-youtube-line', 'link' => 'https://youtube.com/santexnik', 'is_active' => true, 'order' => 4],
        ];

        foreach ($socials as $data) {
            Social::create($data);
        }
    }
}
