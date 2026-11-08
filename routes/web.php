<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogSectionController;
use App\Http\Controllers\BlogSubscriberController;
use App\Http\Controllers\BulkEmailController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\EditorImageController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\MailTemplateController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SectionTextController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\StepController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Panel dili (giriş sayfasında da seçilebilir)
Route::post('/dil', [LocaleController::class, 'update'])->middleware('throttle:30,1')->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
});

// E-postadaki "abonelikten çık" bağlantısı: giriş gerekmez, imzalı adres
Route::middleware(['signed', 'throttle:20,1'])->prefix('abonelik/{subscriber}')->group(function () {
    Route::get('/iptal', [BlogSubscriberController::class, 'unsubscribe'])->name('subscribers.unsubscribe');
    Route::post('/iptal', [BlogSubscriberController::class, 'confirmUnsubscribe'])->name('subscribers.unsubscribe.confirm');
});

// Admin + editör
Route::middleware(['auth', 'role:admin,editor'])->group(function () {
    Route::get('/', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    // Bir kaydı (aktivite, blog, SSS, sayfa) diğer dillere çevir
    Route::get('/ceviri/{type}/{id}/{locale}', [TranslationController::class, 'edit'])->whereNumber('id')->name('translations.edit');
    Route::put('/ceviri/{type}/{id}/{locale}', [TranslationController::class, 'update'])->whereNumber('id')->name('translations.update');
    Route::get('/ceviri-durum', [TranslationController::class, 'jobs'])->name('translations.jobs');
    Route::post('/ceviri/{type}/{id}', [TranslationController::class, 'translate'])->whereNumber('id')->middleware('throttle:20,1')->name('translations.translate');
    Route::post('/editor-gorseli', [EditorImageController::class, 'store'])->middleware('throttle:60,1')->name('editor-images.store');

    // E-posta (info@tripspoiler.com)
    Route::controller(MailboxController::class)->prefix('e-posta')->name('mailbox.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/oku', 'show')->name('show');
        Route::get('/govde', 'body')->name('body');
        Route::get('/ek', 'attachment')->name('attachment');
        Route::get('/yaz', 'compose')->name('compose');
        Route::post('/gonder', 'send')->name('send');
        Route::patch('/okundu', 'seen')->name('seen');
        Route::patch('/yildiz', 'flag')->name('flag');
        Route::post('/tasi', 'move')->name('move');
        Route::delete('/sil', 'destroy')->name('destroy');
    });

    Route::get('/e-posta/toplu', [BulkEmailController::class, 'create'])->name('mailbox.bulk.create');
    Route::post('/e-posta/toplu', [BulkEmailController::class, 'store'])->name('mailbox.bulk.store');

    Route::controller(MailTemplateController::class)->prefix('e-posta/hazir-yanitlar')->name('mailbox.templates.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/imza', 'updateSignature')->name('signature');
        Route::put('/{mailTemplate}', 'update')->name('update');
        Route::delete('/{mailTemplate}', 'destroy')->name('destroy');
    });

    // Aktiviteler
    Route::resource('aktiviteler', ActivityController::class)
        ->except('show')
        ->names('activities')
        ->parameters(['aktiviteler' => 'activity']);
    Route::patch('/aktiviteler/{activity}/durum', [ActivityController::class, 'toggle'])->name('activities.toggle');
    Route::patch('/aktiviteler/{activity}/populer', [ActivityController::class, 'togglePopular'])->name('activities.popular');

    // Blog
    // Şehir rehberi: alanlar (sabit adresler {city}'den önce) ve şehir başına içerik
    Route::controller(\App\Http\Controllers\CityGuideTopicController::class)->prefix('sehir-rehberi/alanlar')->name('city-guide-topics.')->group(function () {
        Route::get('/yeni', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{topic}/duzenle', 'edit')->name('edit');
        Route::put('/{topic}', 'update')->name('update');
        Route::delete('/{topic}', 'destroy')->name('destroy');
    });
    Route::controller(\App\Http\Controllers\CityGuideController::class)->prefix('sehir-rehberi')->name('city-guides.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{city}', 'show')->name('show');
        Route::put('/{city}/giris', 'updateIntro')->name('intro');
        Route::patch('/{city}/sira', 'order')->name('order');
        Route::get('/{city}/alan/{section}', 'editSection')->name('sections.edit');
        Route::put('/{city}/alan/{section}', 'updateSection')->name('sections.update');
    });

    Route::resource('blog', BlogController::class)
        ->except('show')
        ->names('blogs')
        ->parameters(['blog' => 'blog']);
    Route::get('/blog-siralama', [BlogController::class, 'order'])->name('blogs.order');
    Route::patch('/blog-siralama', [BlogController::class, 'saveOrder'])->name('blogs.order.save');
    Route::patch('/blog/{blog}/durum', [BlogController::class, 'toggle'])->name('blogs.toggle');

    // Sıkça sorulan sorular
    Route::resource('sss', FaqController::class)
        ->except('show')
        ->names('faqs')
        ->parameters(['sss' => 'faq']);
    Route::patch('/sss/{faq}/durum', [FaqController::class, 'toggle'])->name('faqs.toggle');

    // Kart sayfalarının sürükle-bırak sırası (Yönetim, Site ayarları)
    Route::patch('/kart-sirasi', [ManagementController::class, 'order'])->name('cards.order');

    // Anasayfa bölümlerinin yazıları (TripSpoiler nedir, Sosyal medya, Yorumlar)
    Route::get('/bolum-yazilari', [SectionTextController::class, 'index'])->name('section-texts.index');
    Route::get('/bolum-yazilari/{key}', [SectionTextController::class, 'edit'])->whereIn('key', ['intro', 'social', 'reviews', 'city_entry', 'partners'])->name('section-texts.edit');
    Route::put('/bolum-yazilari/{key}', [SectionTextController::class, 'update'])->whereIn('key', ['intro', 'social', 'reviews', 'city_entry', 'partners'])->name('section-texts.update');

    // Anasayfadaki blog bölümü
    Route::get('/blog-bolumu', [BlogSectionController::class, 'index'])->name('blog-section.index');
    Route::put('/blog-bolumu', [BlogSectionController::class, 'update'])->name('blog-section.update');

    // Anasayfadaki "Nasıl çalışır" bölümü
    Route::get('/nasil-calisir', [StepController::class, 'index'])->name('steps.index');
    Route::put('/nasil-calisir/yazilar', [StepController::class, 'updateSection'])->name('steps.section');
    Route::get('/nasil-calisir/adim/yeni', [StepController::class, 'create'])->name('steps.create');
    Route::post('/nasil-calisir/adim', [StepController::class, 'store'])->name('steps.store');
    Route::get('/nasil-calisir/adim/{step}/duzenle', [StepController::class, 'edit'])->name('steps.edit');
    Route::put('/nasil-calisir/adim/{step}', [StepController::class, 'update'])->name('steps.update');
    Route::delete('/nasil-calisir/adim/{step}', [StepController::class, 'destroy'])->name('steps.destroy');
    Route::patch('/nasil-calisir/adim/{step}/durum', [StepController::class, 'toggle'])->name('steps.toggle');
    Route::patch('/nasil-calisir/adim/{step}/sira', [StepController::class, 'move'])->name('steps.move');

    // Anasayfadaki özellik kartları
    Route::resource('ozellik-kartlari', FeatureController::class)
        ->except('show')
        ->names('features')
        ->parameters(['ozellik-kartlari' => 'feature']);
    Route::patch('/ozellik-kartlari/{feature}/durum', [FeatureController::class, 'toggle'])->name('features.toggle');
    Route::patch('/ozellik-kartlari/{feature}/sira', [FeatureController::class, 'move'])->name('features.move');

    // Sitenin üstündeki kayan duyuru şeridi
    Route::resource('duyurular', AnnouncementController::class)
        ->except('show')
        ->names('announcements')
        ->parameters(['duyurular' => 'announcement']);
    Route::patch('/duyurular/{announcement}/durum', [AnnouncementController::class, 'toggle'])->name('announcements.toggle');

    // Yorumlar
    Route::resource('yorumlar', ReviewController::class)
        ->except('show')
        ->names('reviews')
        ->parameters(['yorumlar' => 'review']);
    Route::patch('/yorumlar/{review}/onay', [ReviewController::class, 'toggle'])->name('reviews.toggle');

    // Blog aboneleri
    Route::get('/blog-aboneleri', [BlogSubscriberController::class, 'index'])->name('subscribers.index');
    Route::get('/blog-aboneleri/indir', [BlogSubscriberController::class, 'export'])->name('subscribers.export');
    Route::delete('/blog-aboneleri/{subscriber}', [BlogSubscriberController::class, 'destroy'])->name('subscribers.destroy');
});

// Sadece admin
Route::middleware(['auth', 'role:admin'])->prefix('yonetim')->group(function () {
    Route::get('/', [ManagementController::class, 'index'])->name('management.index');
    Route::get('/roller', [ManagementController::class, 'roles'])->name('roles.index');
    Route::get('/islem-kaydi', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::resource('kullanicilar', UserController::class)
        ->except('show')
        ->names('users')
        ->parameters(['kullanicilar' => 'user']);

    Route::get('/ulkeler-ve-sehirler', [LocationController::class, 'index'])->name('locations.index');
    Route::patch('/ulkeler/{country}/durum', [CountryController::class, 'toggle'])->name('countries.toggle');

    Route::resource('sehirler', CityController::class)
        ->except(['index', 'show'])
        ->names('cities')
        ->parameters(['sehirler' => 'city']);
    Route::patch('/sehirler/{city}/durum', [CityController::class, 'toggle'])->name('cities.toggle');
    Route::patch('/sehirler/{city}/url', [CityController::class, 'updateSlug'])->name('cities.slug');

    Route::get('/site-ayarlari', [SiteSettingsController::class, 'index'])->name('site-settings.index');
    Route::patch('/site-ayarlari/bolum/{section}/durum', [SiteSettingsController::class, 'toggleSection'])->name('site-settings.section');
    Route::get('/site-ayarlari/hero', [SiteSettingsController::class, 'hero'])->name('site-settings.hero');
    Route::put('/site-ayarlari/hero', [SiteSettingsController::class, 'updateHero'])->name('site-settings.hero.update');

    Route::get('/sayfalar', [PageController::class, 'index'])->name('pages.index');
    Route::get('/sayfalar/{page}/duzenle', [PageController::class, 'edit'])->name('pages.edit');
    Route::get('/sayfalar/{page}/seo', [PageController::class, 'seo'])->name('pages.seo');
    Route::put('/sayfalar/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::get('/sayfalar/{page}/bolumler', [PageController::class, 'sections'])->name('pages.sections');
    Route::patch('/sayfalar/{page}/bolumler', [PageController::class, 'updateSections'])->name('pages.sections.update');
    Route::get('/sayfalar/{page}/icerik', [\App\Http\Controllers\PageContentController::class, 'edit'])->name('pages.content');
    Route::get('/sayfalar/{page}/icerik/{section}', [\App\Http\Controllers\PageContentController::class, 'section'])->name('pages.content.section');
    Route::put('/sayfalar/{page}/icerik/{section}', [\App\Http\Controllers\PageContentController::class, 'update'])->name('pages.content.update');
    Route::post('/sayfalar/{page}/icerik/{section}/cevir', [\App\Http\Controllers\PageContentController::class, 'translate'])->middleware('throttle:10,1')->name('pages.content.translate');
    Route::get('/sayfalar/{page}/hero', [\App\Http\Controllers\PageHeroTextController::class, 'edit'])->name('pages.hero-text');
    Route::put('/sayfalar/{page}/hero', [\App\Http\Controllers\PageHeroTextController::class, 'update'])->name('pages.hero-text.update');
    Route::get('/sayfalar/{page}/banner', [\App\Http\Controllers\PageHeroController::class, 'edit'])->name('pages.hero');
    Route::put('/sayfalar/{page}/banner', [\App\Http\Controllers\PageHeroController::class, 'update'])->name('pages.hero.update');

    Route::resource('partnerler', PartnerController::class)
        ->except('show')
        ->names('partners')
        ->parameters(['partnerler' => 'partner']);
    Route::patch('/partnerler/{partner}/durum', [PartnerController::class, 'toggle'])->name('partners.toggle');
    Route::patch('/partnerler/{partner}/sitede', [PartnerController::class, 'toggleShowcase'])->name('partners.showcase');

    Route::get('/diller-ve-ceviri', [TranslationController::class, 'index'])->name('translations.index');
    Route::post('/diller-ve-ceviri/toplu', [TranslationController::class, 'translateAll'])->name('translations.all');
    Route::patch('/diller/{language}/durum', [TranslationController::class, 'toggleLanguage'])->name('languages.toggle');

    Route::get('/yonlendirmeler', [RedirectController::class, 'index'])->name('redirects.index');
    Route::post('/yonlendirmeler', [RedirectController::class, 'store'])->name('redirects.store');
    Route::delete('/yonlendirmeler/{redirect}', [RedirectController::class, 'destroy'])->name('redirects.destroy');
});
