# DreamTour CRM: iş jurnalı və davam etmək üçün qeydlər

Son yenilənmə: **16 noyabr 2026**. Production commit: **`c9d5965`**. Staging (`develop`): **`4c89a78`** (Modul 7, 8, 9 və waitlist, production-a çıxmayıb).

Bu sənəd yeni sessiyada (və ya yeni developer üçün) işi qaldığı yerdən davam etdirmək üçündür. Hər modul bitəndə yenilənir.

---

## 1. Mühit

| | Production | Staging |
|---|---|---|
| URL | https://dreamtourpa.com | https://staging.dreamtourpa.com |
| Qovluq | `/var/www/dreamtourpa` | `/var/www/staging.dreamtourpa` |
| Baza | `websites_tours` | `websites_tours_staging` |
| Git branch | `phase-1-tz-fixes` | `develop` |
| Növbə (systemd) | `dreamtour-queue` | `dreamtour-staging-queue` |

- **Stack:** Laravel 13.42, PHP 8.5-FPM, MySQL 8.4 (`ONLY_FULL_GROUP_BY` aktivdir), Nginx, certbot.
- **Repo:** https://github.com/Rashidli/dreamtours
- **Admin teması:** Nazox, Bootstrap **5.1.3**. Versiyanı dəyişmə, tema 5.1 üçün yazılıb.
- **Test bazası:** `websites_tours_test`. `phpunit.xml` bu bazanı məcbur edir.
- **Backup-lar:** `/root/backups/`. Hər deploy-dan əvvəl baza, `.env` və kod (`*before-moduleN-*`).
- **Sənədlər:**
  - CRM tələbləri: `/var/www/dreamtourpa/DreamTour_CRM_Developer_Requirements.pdf`
  - Change request: `/var/www/dreamtourpa/DreamTour_Website_Developer_Change_Request.pdf`

## 2. Dəyişməz qaydalar (istifadəçinin tələbi)

1. **Production canlıdır, data silən heç bir əmr işlətmə:** `migrate:fresh/refresh/reset/rollback`, `db:wipe`, `DROP`, `TRUNCATE`, `DELETE`, overwrite edən seeder və s. Yalnız əlavə edən migration-lar.
2. **Testləri heç vaxt production qovluğunda işlətmə.** Staging-də əvvəlcə `config:clear` et. Bir dəfə keşlənmiş config staging bazasını silib.
3. **Artisan və testləri `sudo -u www-data php artisan …` ilə işlət.** Root kimi işlədəndə `storage/` və `bootstrap/cache`-də root-a məxsus fayllar yaranır və sayt sınır.
4. **Fayl sahibliyini yoxla:**
   - layihə: `root:www-data`;
   - `storage` və `bootstrap/cache`: `www-data:www-data`;
   - `.env`: 640.
5. **Server resurslarını izlə:** disk, RAM, yük.
6. **Git iş axını:** işə başlamazdan əvvəl commit və push, hər task ayrıca commit, sonda hamısını push.
7. **Admin UI qaydaları:**
   - UX/UI qaydalarına əməl et, panel həmişə istifadəçi dostu olsun;
   - siyahılarda qısa xülasə və "Details" düyməsi ilə modal;
   - `small`, `table-sm` və `-sm` inputlarla səhifə qurma, ortaq `crm-*` class-larından istifadə et (bax §5).
8. **Rəqəmlər dəqiq olmalıdır:** pul `bcmath` ilə hesablanır (`App\Support\Money`). Hesabat cəmləri filtrlənmiş sorğunun özündən SQL-də hesablanır. Hər yeni hesabat əl ilə hesablanmış ssenari testi ilə yoxlanır.

## 3. Deploy addımları (hər modul üçün eyni)

```bash
# staging-də: testlər keçib, commit olunub
cd /var/www/staging.dreamtourpa && git push origin develop && git push origin develop:phase-1-tz-fixes

# production
cd /var/www/dreamtourpa
git fetch origin && git merge-base --is-ancestor HEAD origin/phase-1-tz-fixes   # fast-forward olmalıdır
# backup: mysqldump websites_tours | gzip > /root/backups/websites_tours-before-moduleN-$TS.sql.gz ; .env ; tar kod
sudo -u www-data php artisan down --retry=30
git merge --ff-only origin/phase-1-tz-fixes
# composer.lock dəyişibsə: COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader, sonra chown
chown -R www-data:www-data storage bootstrap/cache
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan migrate --pretend --force   # DROP/DELETE/TRUNCATE/RENAME olmamalıdır
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan permission:cache-reset && sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan optimize && systemctl restart dreamtour-queue
sudo -u www-data php artisan up
# yoxla: əsas səhifələr 200, logda production.ERROR yoxdur, sahiblik qaydasındadır
```

- `migrate --pretend` 000009-da xəta verir: dry-run rejimində `insertGetId` `null` qaytarır. Bu zərərsizdir. Belə halda migration kodunu oxuyub yoxla.
- JS yoxlaması: serverdə brauzer yoxdur.
  1. `apt-get install --no-install-recommends nodejs`
  2. Səhifəni HTTP kernel vasitəsilə www-data kimi render et və stdout-a çıxar.
  3. `node --check`
  4. `apt-get purge nodejs node-undici`
- Staging-i production-dan təzələyəndə həm `storage/app/public`, həm `storage/app/private` köçürülməlidir (ödəniş skrinşotları private-dadır).

## 4. Görülən işlər

### Change request (CR 01–06): production-da
- Invoice Request düyməsi silindi.
- "Infant" etiketi əlavə olundu.
- Turlara `show_in_carousel` əlavə olundu.
- Admin tur siyahısına tarix filtrləri və sıralama gəldi.
- Toplu qalereya yükləmə əlavə olundu.
- Yeni email şablonları hazırlandı.
- Əlavə olaraq: HEIC şəkillər, link önizləmələri, 50 MB yükləmə limiti; admin qeydiyyatı bağlandı.

### CRM Faza 1

| # | Modul | Status | Əsas commit-lər |
|---|---|---|---|
| 1 | Audit log / activity history (§22) | ✅ production | `542a51f`, `6687f12` |
| 2 | Müştərilər, import, birləşdirmə (§4, §25) | ✅ production | `50f4fe1`, `2d13937` |
| 3 | Lead pipeline (§3) | ✅ production | `146a483` … `b391a3a` |
| 4 | Ödəniş jurnalı, hesabatlar, qəbzlər (§7) | ✅ production | `127f671`, `9d642b6`, `c54bf92`, `a41ad4c`, `c9041fc` |
| — | Ortaq admin CSS (oxunaqlılıq) | ✅ production | `1fea9a1` |
| 5 | Rollar və icazə redaktoru (§16) | ✅ production | `8ae96ea` |
| 5 | Çıxışa işçi təyini + My trips (§5, §12) | ✅ production | `a2906aa` |
| 6 | Sərnişin siyahısı, check-in, no-show (§11) | ✅ production | `f8be481` |
| 7 | Avtobuslar və oturacaq sxemi (§9, AT-03, AT-06) | 🟦 staging-də hazır | `e08e2be`, `5bd696d` |
| 8 | Dashboard, admin bildirişləri, lead/bron export-ları (§2, §15, §25) | 🟦 staging-də hazır | `3b3919a`, `6fbe6ae`, `352ea62` |
| 9 | Admin-dən bron, sərnişini köçürmək/ləğv etmək, capacity override (§6, §8, AT-05) | 🟦 staging-də hazır | `463fd70` |
| 9b | Gözləmə siyahısı / waitlist (§8) | 🟦 staging-də hazır | `4c89a78` |
| — | `/admin` daxil olmuşu dashboard-a yönləndirir, session regenerate | 🟦 staging | `7cfc5cd` |
| — | Vehicles & seating: yaxınlaşan tarixlər cədvəli | 🟦 staging | `5584671` |

### Modulların qısa xəritəsi (hansı kod harada)

- **Audit:**
  - `App\Support\Activity::record()`;
  - `RecordsActivity` trait: modeldə `$activityIgnore` sahələri tarixçəyə yazılmır, `saveQuietly()` heç nə yazmır;
  - cədvəl: `activity_logs` (sütunlar `actor_id`, `subject_*`, `changes` JSON).
- **Müştərilər:**
  - `App\Support\Customers` uyğunlaşdırır: e-poçt ilə, e-poçt yoxdursa telefonla;
  - `Customer::withStats()`: `total_paid` = `amount_paid`-in cəmi (xalis), `outstanding` = `outstanding_amount`-un cəmi.
- **Lead-lər:** `App\Support\Leads`, `RememberAttribution` middleware (UTM məlumatı).
- **Ödənişlər:**
  - `App\Support\BookingMoney`: `summary()`, `sync()`, `record`, `confirm`, `reject`, `refund`, `void`, `adjust`;
  - `bookings.amount_paid` = **xalis** (ödənişlər − geri qaytarmalar), `outstanding_amount` bu ledger-dən hesablanır;
  - `default_deposit_percent` = **50** (sənəd §7, Settings-dən dəyişir), `balance_due_days` = 7;
  - əmr: `crm:sync-payments [--dry-run]`.
- **Rollar:**
  - `App\Support\StaffRoles::DEFAULTS` rolları yalnız mövcud deyilsə yaradır; seeder `install(topUp: true)` işlədir;
  - `App\Support\PermissionGroups`: redaktor üçün qrid, insan dilində adlar, `ROLE_HINTS`;
  - superadmin `Gate::before` ilə bütün icazələrə malikdir.
- **Maliyyə görünürlüğü:** `view-financial-info` icazəsi. Bu icazə olmadan məbləğlər, qəbzlər, pul filtrləri və CSV-dəki pul sütunları bağlıdır. Yoxlama serverdə aparılır.
- **İşçi təyini:**
  - `departure_staff` cədvəli (rollar: leader, driver, guide, coordinator);
  - `DepartureStaffController` (`assign-departure-staff` icazəsi);
  - `MyTripsController`: tura giriş = **cari təyinat** VƏ YA `list-bookings` (ofis baxışı);
  - `users.phone` sütunu;
  - lider daxil olanda `home` onu My trips-ə yönləndirir.
- **Check-in:**
  - `App\Support\Attendance`:
    - irəli getmək üçün `check-in-passengers` kifayətdir;
    - geri qaytarmaq üçün `undo-check-in` lazımdır, yalnız öz qeydini 10 dəqiqə ərzində düzəltmək istisnadır;
    - yola düşməyə 30 dəqiqə qalanda "missing" xəbərdarlığı göstərilir.
  - `App\Support\Roster`: filtrlər, sıralama, export sütunları; liderin nüsxəsində bron statusu və pul yoxdur.
  - `App\Support\RosterExport`: csv, xlsx (openspout), pdf (dompdf); hər yükləmə activity log-a yazılır.
  - Tarixçə: `passenger_attendance_events`.
- **Avtobuslar və oturacaqlar (Modul 7):**
  - `vehicle_layouts`: şablonlar, `VehicleLayout::generate/clean/seatLabels`; layout JSON = `{rows, cols, cells[[{t, l?}]]}`, növlər: seat, leader, driver, door, aisle, empty;
  - `departure_vehicles`: tarixin avtobusları; şablonun **kopyası**, `blocked`, `seats_changed_at` (publish-dən sonrakı dəyişiklik = qaralama), `published_at`;
  - `booking_passengers.departure_vehicle_id` + `seat_number`; **unique(departure_vehicle_id, seat_number)**; `seat_notified` = qonağa son göndərilən yer;
  - `App\Support\Seating`: `place()` (bütün dəyişikliklər, kilid + toqquşma yoxlaması), `block`, `autofill`, `release` (ləğvdə), `publish`, `state` (lövhə JSON);
  - `SeatNotices::blocker`: avtobuslu tarixdə qaralama varsa e-poçt göndərilmir;
  - icazələr: `list/create/edit/delete-vehicle-layouts`, `manage-seating` (yalnız admin);
  - səhifələr: Admin › Vehicles & seating; tarix › Seating (`/admin/departures/{id}/seating`).
- **Dashboard (Modul 8a):** `App\Support\Dashboard::for($user)` — hər kart yalnız icazə varsa və öz filtrli linki ilə; bron siyahısına `booked_from/booked_to` filtri əlavə olunub.
- **Admin bildirişləri (Modul 8b):** `staff_alerts` (unique `dedupe_key`), `App\Support\Alerts` (`TYPES` = növ → icazə, `raise`, `resolve`, `listen()` model hadisələri, `checkCapacity`, `sweep`/`sweepIfDue` 10 dəq-dən bir, əmr `crm:alerts`). Header zəngi sayı göstərir, siyahı `alerts.dropdown`-dan yüklənir. Hədlər: Settings › Staff alerts; növləri açıb-söndürmək: Alerts səhifəsi (`alerts_off`). Yalnız in-app.
- **Bron nəzarəti (Modul 9):** `ManualBookingController` (yeni bron `bookings/new`, müştəri axtarışı `bookings/customer-lookup`, `movePassenger`, `cancelPassenger`); `App\Support\BookingSplit` (sərnişin öz bronuna ayrılır, `bookings.split_from_booking_id`; heç nə silinmir); `App\Support\Capacity::claim()` — yer əlavə edən bütün admin yolları buradan keçir, artıq bron yalnız `override-capacity` + səbəb ilə (activity `capacity_override`). İcazələr: `create-bookings` (admin, Sales Manager), `override-capacity` (admin).
- **Gözləmə siyahısı:** `waitlist_entries` (status waiting/offered/booked/removed, `position`), `App\Support\Waitlist` (`open`, `add`, `offer`, `booked`, `remove`, `move`, `seatsFreed` — `Alerts::checkCapacity` yer boşalanda çağırır). Sayt: `tours/{slug}/waitlist?departure=` (yalnız dolu gələcək tarix; honeypot + throttle). Admin: `Admin › Waitlist`; "Book" → `bookings/new?waitlist=ID`. E-poçt: `WaitlistSeatAvailable`. Bildirişlər: `waitlist_joined`, `waitlist_seat_free`.
- **Export-lar (Modul 8c):** `App\Support\TableExport` (csv/xlsx); `export-leads`, `export-bookings`; bron export-unda pul sütunları yalnız `view-financial-info` ilə.

### Rollar (production)

| Rol | Nə üçündür |
|---|---|
| superadmin | Hər şey (yeganə istifadəçi #1) |
| admin | Bütün CRM, texniki ayarlar istisna |
| Sales Manager | Lead-lər, müştərilər, bronlar, ödəniş qeydi |
| Content Manager | Turlar, səhifələr, kontent; bron və maliyyə yoxdur |
| Accountant | Ödənişlər, geri qaytarmalar, endirimlər, hesabatlar |
| Tour Leader | Yalnız My trips + check-in |
| editor, test | Köhnə rollar, istifadəçisi yoxdur |

### Texniki SEO (2026-11-17, production-da)

- **Branch:** `seo`, `phase-1-tz-fixes`-dən ayrılıb (modul 7–9 production-a getməsin deyə). Production-a fast-forward ilə çıxıb, `develop`-a merge olunub.
- **Kod:**
  - `App\Support\Seo`: canonical (APP_URL + path, query-siz) və JSON-LD. Sayt üzrə TravelAgency + WebSite; tur səhifəsində Product/TouristTrip (offers, aggregateRating), BreadcrumbList, FAQPage; Contact-da FAQPage.
  - Səhifə əlavə blokları `$seoSchema` ilə ötürür, `social_meta` hamısını çap edir.
  - `SitemapController`: `/sitemap.xml` və `/robots.txt` (production-dan başqa yerdə `Disallow: /`). `public/robots.txt` silinib.
  - `SocialImage::photo()`: turun öz şəkli 1200×630 kart olur. Xam yolu `getRawOriginal('main_image')` ilə ver, çünki accessor URL qaytarır.
  - `front.partials.data`: `gallery` göndərilmir, `galleryAlbums` yalnız Gallery səhifəsində göndərilir (~95 KB qənaət). Admin login `noindex`-dir. Testlər: `tests/Feature/SeoTest.php`.
- **nginx** (repoda deyil, backup: `/root/backups/nginx-*-before-seo-20261117-070629*`):
  - `snippets/dreamtour-performance.conf` (CSS/JS/JSON/SVG üçün gzip);
  - HSTS;
  - `/index.php` → təmiz URL;
  - statik fayllar 30 gün keşlənir, `/assets/` (admin) keşlənmir;
  - production-da `www` → `https://dreamtourpa.com` 301.

### Sayt taskları branch-ı `site-tasks` (2026-11-18; production-a çıxıb 2026-11-19, 7bfeaf2)

- Bu branch `phase-1-tz-fixes`-dən ayrılıb, `develop`-a merge olunur. Production-a `git push origin site-tasks:phase-1-tz-fixes` ilə çıxacaq.
- **Tur SEO** (f4a2a88), migrasiya `2026_11_18_000001` (əlavə edir):
  - `tour_translations.meta_title`, `meta_description`; `tour_slug_redirects` cədvəli.
  - Admin formada `admin/tours/_seo.blade.php` bloku var.
  - `TourTranslation::$slugLocked`: ad dəyişəndə slug dəyişmir.
  - Köhnə slug `TourSlugRedirect::redirectFor` ilə 301 verir (tur və book səhifələri).
  - `GeneratesSlug` trait-i əl ilə yazılmış slug-ı saxlayır (bütün 6 model üçün).
- **Tur formasının yeni dizaynı** (56bc6da):
  - İki sütun: solda bölmə kartları (`tf-section`), sağda yapışqan Publish, şəkil və bölmə menyusu.
  - Stil faylı `public/assets/css/admin-tour-form.css`-dir; `admin-crm.css`-ə toxunulmayıb, çünki develop-da da dəyişib.
  - Sahə adları dəyişməyib. Kruiz bölməsi switch-ə bağlıdır.

- **Production deploy (2026-11-19):**
  - Branch-da tur SEO, formanın yeni dizaynı, dublikat və siyahı filtrləri var.
  - Migrasiya `2026_11_18_000001` işlədilib.
  - Backup: `/root/backups/*before-site-tasks-20261119-160015*`.
- **Admin JS qeydi:** Bootstrap JS 5.0.2-dir (CDN), CSS isə 5.1.3. Bootstrap dropdown-un 5.1 imkanları işləmir. Tur siyahısının filtr menyuları öz toggle-ından istifadə edir.
- **SMTP vəziyyəti:**
  - Mail.ru (VK WorkSpace) pulsuz tarifdə xarici proqram parolu yaratmağa icazə vermir. Tarifi dəyişmək və hesaba telefon bağlamaq lazımdır.
  - Alternativ: Brevo və ya oxşar SMTP xidməti. Bunun üçün DNS-də SPF-ə `include` əlavə etmək və DKIM qeydi yaratmaq lazımdır.
  - İstifadəçi qərarı gözlənilir. Production hələ də `MAIL_MAILER=log` rejimindədir.

### Admin redizaynı, branch `admin-design` (2026-11-19, yalnız staging-də)

- **Dizayn dili:** Polaris-dən ilhamlanıb (Shopify admin), CSS tamamilə bizimdir.
  - Ümumi qat `public/assets/css/admin-ui.css` header-də sonuncu yüklənir.
  - Çərçivə: kömür rəngli menyu (#26282c), ağ üst panel.
  - Hər bölmənin öz CSS-i var: `admin-<area>.css`. Siyahı/forma nümunələri `admin-tour-form.css`-dədir (`tl-*`, `tf-*`).
- **Hazır olanlar:**
  - Tur siyahısı, tur redaktəsi (yapışqan bölmə zolağı və Save), menyu (yenidən qruplaşdırılıb), üst panel.
  - Qlobal axtarış: `admin.search`, `SearchController`.
  - Giriş səhifəsi: dəqiqədə 6 cəhd limiti, sessiya yenilənir, "remember me" yoxdur.
  - 7 paralel agentlə: Customers, Bookings, Leads, Payments, Komanda və tənzimləmələr, CMS, Kataloq və viza.
- **develop ilə merge:** Bookings, Leads və Customers səhifələrinə modul 7–9 əlavələri yeni markup-a köçürülüb (split düymələri, `capacity_override`, export, "New booking"). Menyudakı vehicles/waitlist bəndləri `Route::has` ilə yoxlanılır.
- **Ekran görüntüsü aləti:** `/opt/admin-shots` (`render.php` `APP_ROOT` qəbul edir; `shoot.py` seçimləri: `--url`, `--css2`, `--at`, `--clip`, `--click`).
- **Dashboard** (2026-11-20, `develop`-da, çünki modul 8-ə aiddir):
  - `resources/views/home.blade.php` yenidən quruldu, stil faylı `admin-dashboard.css`.
  - "Needs attention" siyahısı, neytral göstəricilər zolağı, yaxın səfərlər.
  - Doluluq oflayn saxlanmış yerlər daxil hesablanır.
  - Testlər: `DashboardTest`.
- **Hələ yenilənməyən admin səhifələri** (quruluşu köhnədir, amma ümumi stil tətbiq olunur):
  1. `tours/passengers` və `roster/*`: roster, check-in (production-da; liderlər mobildə işlədir).
  2. `my_trips/index` və `my_trips/show`: liderin səfərləri (production-da; mobil).
  3. `vehicles/*` və `seating/board`: avtobuslar, oturacaq planı (modul 7, staging).
  4. `bookings/create`: işçi bronu (modul 9, staging).
  5. `waitlist/index` (staging).
  6. `alerts/*`: bildirişlər səhifəsi və zəngin açılan siyahısı (modul 8, staging).
  - Ortaq kiçik işlər:
    - `includes/media_field`: önizləmə 64px-dir; agentlərin böyük önizləməli variantına keçmək.
    - `activity_log/entry`: kiçik mətn.
    - `flash`, `validation_errors`, `lang_tabs`, `capacity_override`: yalnız ümumi stil tətbiq olunur.
  - Qəsdən toxunulmayanlar: PDF-lər (`receipts/*`, `roster/pdf`, `seating/pdf`) və `vendor/pagination`.
  - Tövsiyə olunan sıra: 1–2, sonra 3–6. Paralel aparmaq olar (3 agent).
- **Növbəti UX işi (istifadəçi istədi):** müştəri detal səhifəsi (`/admin/customers/{id}`). Yalnız görünüş yox, istifadə rahatlığı da düşünülməlidir.

## 5. UI qaydaları (ortaq CSS)

- **Fayl:** `public/assets/css/admin-crm.css`. `admin/includes/header` vasitəsilə bütün admin səhifələrinə yüklənir.
- **Class-lar:**
  - `crm-tile`: cəm kartı, rəngli üst xətlə: `style="border-top:3px solid var(--bs-success)"`;
  - `crm-filters`: filtr kartı, etiketlər qalın;
  - `crm-table`: cədvəl, `.sub` boz ikinci sətir, `.amount` sağa düzülü qalın məbləğ;
  - `crm-group`, `crm-empty`.
- Bu fayl həm də bütün admin üçün kiçik mətn və `-sm` inputlara minimal ölçü təyin edir.
- **Temada olmayan class-lar:** `btn-soft-*`, `*-subtle`, `nav-tabs-custom`. Əvəzinə `btn-outline-*` və `bg-x bg-opacity-10 text-x` işlət.
- **Blade tələsi:** eyni faylda `@php(...)` inline forması ilə `@php … @endphp` blokunu qarışdırma, kompilyasiya pozulur.

## 6. Testlər

- **İşlətmək:**
  ```bash
  cd /var/www/staging.dreamtourpa
  sudo -u www-data php artisan config:clear && sudo -u www-data php artisan test
  ```
- **Əvvəldən uğursuz olan 3 köhnə test** (bizim işə aid deyil, meyar: "yeni uğursuz test olmasın"):
  - `booking rejects more seats than available`;
  - `tour detail exposes booking anchor and posts its form`;
  - `departure and itinerary nested cruds validate relations`.
- **CRM testləri:** `ActivityLogTest`, `CustomerTest`, `CustomerImportMergeTest`, `LeadTest`, `PaymentLedgerTest`, `PaymentReportTest`, `StaffRolesTest`, `DepartureStaffTest`, `AttendanceTest`.
- Testlərdən sonra staging-də yenidən `config:cache` və `route:cache` et.

## 7. Açıq məsələlər və növbəti addımlar

- **Modul 7, 8, 9 və waitlist staging-dədir, production deploy gözləyir** (istifadəçi hələ çıxarmamağı dedi, 2026-11-16). Deploy: migrate `2026_11_16_000001`…`000006` (hamısı əlavə edir) → `permission:cache-reset` → `cache:clear`.
- **Laravel scheduler heç yerdə işləmir** (cron yoxdur): `bookings:send-seat-notices` (hər gün 09:00) heç vaxt işə düşmür. Qoşmaq üçün istifadəçinin razılığı lazımdır (`* * * * * www-data php artisan schedule:run`), çünki qonaqlara e-poçt göndərməyə başlayacaq.
- **Modul 8:** dashboard (yalnız sayların olduğu köhnə `PageController::home`), export-lar, admin bildirişləri (§15).
- **Check-in oflayn rejimi** (§11 "tövsiyə") hələ yoxdur.
- **Referral Partner rolu** Faza 2-yə (partnyor portalı) aiddir.
- **SMTP:** `noreply@dreamtourpa.com` məlumatları gözlənilir. Production-da `MAIL_MAILER=log`-dur, e-poçtlar `storage/logs/mail-*.log`-a yazılır.
- **DNS:** `_dmarc` TXT qeydi yoxdur.
