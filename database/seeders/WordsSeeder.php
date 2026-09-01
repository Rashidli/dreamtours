<?php

namespace Database\Seeders;

use App\Models\Word;
use Illuminate\Database\Seeder;

class WordsSeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            // Navigation & Pages
            'home' => ['az' => 'Ana Səhifə', 'en' => 'Home', 'ru' => 'Главная'],
            'about' => ['az' => 'Haqqımızda', 'en' => 'About Us', 'ru' => 'О нас'],
            'about_us' => ['az' => 'Haqqımızda', 'en' => 'About Us', 'ru' => 'О нас'],
            'services' => ['az' => 'Xidmətlər', 'en' => 'Services', 'ru' => 'Услуги'],
            'portfolio' => ['az' => 'Portfolio', 'en' => 'Portfolio', 'ru' => 'Портфолио'],
            'blog' => ['az' => 'Bloq', 'en' => 'Blog', 'ru' => 'Блог'],
            'contact' => ['az' => 'Əlaqə', 'en' => 'Contact', 'ru' => 'Контакты'],

            // Contact Page
            'get_in_touch' => ['az' => 'Bizimlə əlaqə saxlayın', 'en' => 'Get in Touch', 'ru' => 'Свяжитесь с нами'],
            'contact_description' => ['az' => 'Suallarınız və ya xidmət sifarişləriniz üçün aşağıdakı vasitələrlə bizimlə əlaqə saxlaya bilərsiniz.', 'en' => 'You can contact us through the following means for your questions or service orders.', 'ru' => 'Вы можете связаться с нами по следующим контактам для вопросов или заказов.'],
            'send_message' => ['az' => 'Mesaj göndərin', 'en' => 'Send Message', 'ru' => 'Отправить сообщение'],
            'full_name' => ['az' => 'Ad, Soyad', 'en' => 'Full Name', 'ru' => 'Имя, Фамилия'],
            'enter_name' => ['az' => 'Adınızı daxil edin', 'en' => 'Enter your name', 'ru' => 'Введите ваше имя'],
            'phone' => ['az' => 'Telefon', 'en' => 'Phone', 'ru' => 'Телефон'],
            'enter_phone' => ['az' => 'Telefon nömrənizi daxil edin', 'en' => 'Enter your phone number', 'ru' => 'Введите номер телефона'],
            'service' => ['az' => 'Xidmət', 'en' => 'Service', 'ru' => 'Услуга'],
            'select_service' => ['az' => 'Xidmət seçin', 'en' => 'Select Service', 'ru' => 'Выберите услугу'],
            'message' => ['az' => 'Mesaj', 'en' => 'Message', 'ru' => 'Сообщение'],
            'enter_message' => ['az' => 'Mesajınızı yazın', 'en' => 'Enter your message', 'ru' => 'Введите сообщение'],
            'send' => ['az' => 'Göndər', 'en' => 'Send', 'ru' => 'Отправить'],

            // About Section
            'years_experience' => ['az' => 'İllik təcrübə', 'en' => 'Years Experience', 'ru' => 'Лет опыта'],
            'quality_service' => ['az' => 'Keyfiyyətli xidmət', 'en' => 'Quality Service', 'ru' => 'Качественный сервис'],
            'professional_team' => ['az' => 'Peşəkar komanda', 'en' => 'Professional Team', 'ru' => 'Профессиональная команда'],
            'fast_service' => ['az' => 'Sürətli xidmət', 'en' => 'Fast Service', 'ru' => 'Быстрый сервис'],
            'warranty' => ['az' => 'Zəmanət', 'en' => 'Warranty', 'ru' => 'Гарантия'],
            'learn_more' => ['az' => 'Daha ətraflı', 'en' => 'Learn More', 'ru' => 'Подробнее'],
            'happy_customers' => ['az' => 'Razı müştəri', 'en' => 'Happy Customers', 'ru' => 'Довольных клиентов'],
            'service_available' => ['az' => 'Xidmət mövcuddur', 'en' => 'Service Available', 'ru' => 'Сервис доступен'],

            // Why Choose Us
            'why_choose_us' => ['az' => 'Niyə bizi seçməlisiniz?', 'en' => 'Why Choose Us?', 'ru' => 'Почему выбирают нас?'],
            'why_choose_us_desc' => ['az' => 'Peşəkar xidmətlərimizlə fərqlənən üstünlüklərimiz', 'en' => 'Our advantages with professional services', 'ru' => 'Наши преимущества профессиональных услуг'],

            // Services Section
            'our_services' => ['az' => 'Xidmətlərimiz', 'en' => 'Our Services', 'ru' => 'Наши услуги'],
            'services_description' => ['az' => 'Sizə təklif etdiyimiz peşəkar xidmətlər', 'en' => 'Professional services we offer you', 'ru' => 'Профессиональные услуги для вас'],
            'other_services' => ['az' => 'Digər xidmətlər', 'en' => 'Other Services', 'ru' => 'Другие услуги'],
            'no_services' => ['az' => 'Hal-hazırda xidmət yoxdur', 'en' => 'No services available', 'ru' => 'Услуги недоступны'],
            'order_service' => ['az' => 'Sifariş et', 'en' => 'Order Service', 'ru' => 'Заказать услугу'],

            // Common Actions
            'read_more' => ['az' => 'Ətraflı', 'en' => 'Read More', 'ru' => 'Подробнее'],
            'view_all' => ['az' => 'Hamısına bax', 'en' => 'View All', 'ru' => 'Смотреть все'],
            'call_now' => ['az' => 'Zəng et', 'en' => 'Call Now', 'ru' => 'Позвонить'],
            'get_quote' => ['az' => 'Qiymət alın', 'en' => 'Get Quote', 'ru' => 'Получить цену'],
            'contact_us' => ['az' => 'Əlaqə saxla', 'en' => 'Contact Us', 'ru' => 'Связаться'],

            // CTA Section
            'cta_title' => ['az' => 'Peşəkar xidmət üçün bizimlə əlaqə saxlayın', 'en' => 'Contact us for professional service', 'ru' => 'Свяжитесь с нами для профессионального обслуживания'],
            'cta_description' => ['az' => 'İşlərinizi keyfiyyətlə yerinə yetirmək üçün 24/7 sizin xidmətinizdəyik', 'en' => 'We are at your service 24/7 to complete your work with quality', 'ru' => 'Мы к вашим услугам 24/7 для качественного выполнения работ'],
            'need_help' => ['az' => 'Kömək lazımdır?', 'en' => 'Need Help?', 'ru' => 'Нужна помощь?'],
            'call_anytime' => ['az' => '24/7 zəng edə bilərsiniz', 'en' => 'You can call 24/7', 'ru' => 'Звоните 24/7'],

            // Portfolio Section
            'our_work' => ['az' => 'İşlərimiz', 'en' => 'Our Work', 'ru' => 'Наши работы'],
            'portfolio_description' => ['az' => 'Tamamladığımız layihələrdən nümunələr', 'en' => 'Examples of our completed projects', 'ru' => 'Примеры выполненных проектов'],
            'no_portfolios' => ['az' => 'Hal-hazırda portfolio yoxdur', 'en' => 'No portfolio available', 'ru' => 'Портфолио недоступно'],
            'project_details' => ['az' => 'Layihə haqqında', 'en' => 'Project Details', 'ru' => 'О проекте'],
            'client' => ['az' => 'Müştəri', 'en' => 'Client', 'ru' => 'Клиент'],
            'private_client' => ['az' => 'Şəxsi müştəri', 'en' => 'Private Client', 'ru' => 'Частный клиент'],
            'date' => ['az' => 'Tarix', 'en' => 'Date', 'ru' => 'Дата'],
            'location' => ['az' => 'Ünvan', 'en' => 'Location', 'ru' => 'Адрес'],
            'baku' => ['az' => 'Bakı, Azərbaycan', 'en' => 'Baku, Azerbaijan', 'ru' => 'Баку, Азербайджан'],

            // Reviews Section
            'customer_reviews' => ['az' => 'Müştəri rəyləri', 'en' => 'Customer Reviews', 'ru' => 'Отзывы клиентов'],
            'reviews_description' => ['az' => 'Müştərilərimizin haqqımızda fikirləri', 'en' => 'What our customers say about us', 'ru' => 'Что говорят наши клиенты о нас'],

            // Blog Section
            'latest_news' => ['az' => 'Son xəbərlər', 'en' => 'Latest News', 'ru' => 'Последние новости'],
            'blog_description' => ['az' => 'Faydalı məqalələr və yeniliklər', 'en' => 'Useful articles and updates', 'ru' => 'Полезные статьи и новости'],
            'no_blogs' => ['az' => 'Hal-hazırda bloq yazısı yoxdur', 'en' => 'No blog posts available', 'ru' => 'Блог-посты недоступны'],
            'views' => ['az' => 'baxış', 'en' => 'views', 'ru' => 'просмотров'],
            'tags' => ['az' => 'Etiketlər', 'en' => 'Tags', 'ru' => 'Теги'],
            'share' => ['az' => 'Paylaş', 'en' => 'Share', 'ru' => 'Поделиться'],
            'search' => ['az' => 'Axtar', 'en' => 'Search', 'ru' => 'Поиск'],
            'search_placeholder' => ['az' => 'Açar söz yazın...', 'en' => 'Enter keyword...', 'ru' => 'Введите ключевое слово...'],
            'recent_posts' => ['az' => 'Son yazılar', 'en' => 'Recent Posts', 'ru' => 'Последние записи'],
            'related_posts' => ['az' => 'Oxşar yazılar', 'en' => 'Related Posts', 'ru' => 'Похожие записи'],

            // FAQ Section
            'faq' => ['az' => 'Tez-tez verilən suallar', 'en' => 'FAQ', 'ru' => 'Часто задаваемые вопросы'],
            'faq_description' => ['az' => 'Ən çox soruşulan suallara cavablar', 'en' => 'Answers to frequently asked questions', 'ru' => 'Ответы на часто задаваемые вопросы'],

            // Success Page
            'success' => ['az' => 'Uğurla göndərildi', 'en' => 'Successfully Sent', 'ru' => 'Успешно отправлено'],
            'thank_you' => ['az' => 'Təşəkkür edirik!', 'en' => 'Thank You!', 'ru' => 'Спасибо!'],
            'success_message' => ['az' => 'Müraciətiniz uğurla qəbul edildi. Ən qısa zamanda sizinlə əlaqə saxlanılacaq.', 'en' => 'Your request has been received. We will contact you shortly.', 'ru' => 'Ваша заявка принята. Мы свяжемся с вами в ближайшее время.'],
            'back_to_home' => ['az' => 'Ana səhifəyə qayıt', 'en' => 'Back to Home', 'ru' => 'На главную'],

            // Footer
            'footer_description' => ['az' => 'Peşəkar elektrik və santexnik xidmətləri ilə evinizi güvənli saxlayırıq. Keyfiyyətli iş və müştəri məmnuniyyəti bizim prioritetimizdir.', 'en' => 'We keep your home safe with professional electrical and plumbing services. Quality work and customer satisfaction is our priority.', 'ru' => 'Мы обеспечиваем безопасность вашего дома профессиональными электрическими и сантехническими услугами. Качественная работа и удовлетворенность клиентов - наш приоритет.'],
            'quick_links' => ['az' => 'Keçidlər', 'en' => 'Quick Links', 'ru' => 'Быстрые ссылки'],
            'contact_info' => ['az' => 'Əlaqə', 'en' => 'Contact Info', 'ru' => 'Контактная информация'],
            'all_rights_reserved' => ['az' => 'Bütün hüquqlar qorunur.', 'en' => 'All rights reserved.', 'ru' => 'Все права защищены.'],
            'developed_by' => ['az' => 'Hazırladı', 'en' => 'Developed by', 'ru' => 'Разработано'],
        ];

        foreach ($words as $key => $translations) {
            $word = Word::firstOrCreate(['key' => $key]);

            foreach ($translations as $locale => $title) {
                $word->translateOrNew($locale)->title = $title;
            }

            $word->save();
        }
    }
}
