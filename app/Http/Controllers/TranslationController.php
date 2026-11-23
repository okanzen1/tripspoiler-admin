<?php

namespace App\Http\Controllers;

use App\Support\BulkTranslation;
use App\Support\Queues;
use App\Models\Language;
use App\Models\TranslationState;
use App\Services\Translation\ModelTranslator;
use App\Support\HtmlSanitizer;
use App\Support\TranslationJobs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Yönetim → Diller ve çeviri: sitenin dilleri, dil dil ya da tüm dillere toplu çeviri (ücretsiz Google Çeviri).
 * Kayıt sayfalarındaki çeviri çubuğu da buradan (translate): tek dil ya da tüm diller.
 */
class TranslationController extends Controller
{
    public function __construct(private ModelTranslator $translator) {}

    public function index()
    {
        $languages = Language::ordered()->get();

        // Sayılar panelin hazır listesinden (App\Support\TranslationTodo, arka planda yenilenir): sayfa bütün kayıtları
        // taramaz. Tür başına kayıt sayısı ve dil başına çevirisi eksik / eskimiş kayıt sayısı.
        $todo = \App\Support\TranslationTodo::items();
        $types = [];
        foreach (ModelTranslator::TYPES as $type => $class) {
            $total = $class::count();
            $types[$type] = ['total' => $total, 'ok' => max(0, $total - $todo->where('type', $type)->count())];
        }
        $pendingByLocale = [];
        foreach ($todo as $item) {
            foreach ($item['missing'] ?? [] as $locale) {
                $pendingByLocale[$locale] = ($pendingByLocale[$locale] ?? 0) + 1;
            }
        }

        return view('admin.translations.index', [
            'languages' => $languages,
            'types' => $types,
            'pendingByLocale' => $pendingByLocale,
            'queued' => Queues::bulkCount(),
        ]);
    }

    public function toggleLanguage(Request $request, Language $language)
    {
        abort_if($language->isSource(), 422, __('Kaynak dil (İngilizce) kapatılamaz.'));

        $language->update(['active' => !$language->active]);
        $message = __($language->active ? ':name sitede açıldı.' : ':name sitede kapatıldı.', ['name' => $language->name]);

        return $request->expectsJson()
            ? response()->json(['active' => $language->active, 'message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Çevirisi eksik ya da eskimiş kayıtları toplu çeviri sırasına alır: tüm diller ya da tek dil (locale).
     */
    public function translateAll(Request $request)
    {
        $locale = $request->validate(['locale' => ['nullable', Rule::in(Language::targets()->pluck('code'))]])['locale'] ?? null;

        return $this->queued(BulkTranslation::queue(BulkTranslation::pendingKeys($locale), $locale, $request->user()?->id));
    }

    /**
     * "Çevirisi eksik olanlar" kutusundaki "Tümünü sırayla çevir": listedeki kayıtlar (type:id) toplu çeviri sırasına
     * alınır; arka planda kayıt kayıt (her kaydın bütün dilleri bitince sıradaki) çevrilir.
     */
    public function translateList(Request $request)
    {
        $keys = $request->validate(['items' => ['required', 'array', 'max:2000'], 'items.*' => ['string', 'max:60']])['items'];

        return $this->queued(BulkTranslation::queue($keys, null, $request->user()?->id));
    }

    private function queued(int $count)
    {
        if (!$count) {
            return back()->with('success', __('Tüm çeviriler zaten güncel.'));
        }
        $message = __(':count kayıt çeviri sırasına alındı. Arka planda kayıt kayıt çevrilir; bitince bildirim gelir.', ['count' => $count]);

        $response = back()->with('success', $message);

        return Queues::schedulerAlive() ? $response : $response->with('error', __('Dikkat: zamanlayıcı (cron) şu an çalışmıyor, sıradaki işler başlamaz.'));
    }

    /**
     * Tek kaydı hemen çevirir. locale verilirse sadece o dil (güncel olsa da yeniden), "all" = tüm diller yeniden,
     * hiçbiri yoksa eksik ve eskimiş diller.
     */
    public function translate(Request $request, string $type, int $id)
    {
        $model = (ModelTranslator::TYPES[$type] ?? abort(404))::findOrFail($id);
        $targets = Language::targets()->pluck('code')->all();
        $data = $request->validate(['locale' => ['nullable', Rule::in($targets)], 'all' => ['boolean']]);

        $locales = match (true) {
            filled($data['locale'] ?? null) => [$data['locale']],
            $request->boolean('all') => $targets,
            default => null,
        };

        // Her zaman arka planda (sırada) çevrilir: panel isteği beklemez
        $response = $this->translateInBackground($type, $model, $locales);
        if ($request->expectsJson()) {
            return $response;
        }
        $data = $response->getData(true);

        return back()->with('success', $data['message'] ?? __('Çeviri sıraya alındı; bitince bildirim gelir.'));
    }

    /**
     * Çeviriyi sıraya alır ve hemen döner; diller arka planda çevrilir, ilerleme TranslationJobs'ta, bitince bildirim gelir.
     *
     * @param  list<string>|null  $locales
     */
    private function translateInBackground(string $type, Model $model, ?array $locales)
    {
        $key = $type . ':' . $model->getKey();
        if (TranslationJobs::running($key)) {
            return response()->json(['job' => TranslationJobs::get($key)]);
        }

        $force = $locales !== null;
        $locales ??= $this->translator->fullPending($model); // kayıt + SSS'leri
        if (!$locales) {
            return response()->json(['message' => __('Tüm çeviriler zaten güncel.')]);
        }

        $owner = rescue(fn () => ModelTranslator::owner($type, $model), ['url' => url()->previous(), 'title' => __('Çeviri')], false);
        $title = count($locales) === 1
            ? $owner['title'] . ' · ' . Language::where('code', $locales[0])->value('native_name')
            : $owner['title'];
        $job = TranslationJobs::start($key, (string) $title, url()->previous() ?: $owner['url'], $locales);

        // Diller sırayla, birer iş olarak, öncelikli sırada (toplu çevirinin arkasında beklemez). Çeviri hiçbir zaman
        // bu istekte yapılmaz: eskiden zamanlayıcı geç kalınca panelin kendi süreçlerinde çevriliyordu ve panel kilitleniyordu.
        TranslationJobs::wait($key, 900, __('Sıraya alındı; birazdan başlayacak.'));
        \Illuminate\Support\Facades\Bus::chain([
            ...array_map(fn ($locale) => new \App\Jobs\TranslateLocale($type, (int) $model->getKey(), $locale, $key, $force), $locales),
            new \App\Jobs\FinishTranslation($key, $locales, \Illuminate\Support\Str::snake(class_basename($model)), (int) $model->getKey(), (string) $title),
        ])->onQueue(Queues::HIGH)->dispatch();

        return response()->json(['job' => TranslationJobs::get($key), 'warning' => Queues::schedulerAlive() ? null : __('Zamanlayıcı (cron) şu an çalışmıyor; çeviri o çalışınca başlar.')]);
    }

    /** Panelin sağ üstteki çeviri bildirimi için işlerin durumu */
    public function jobs(Request $request)
    {
        $keys = array_slice(array_filter((array) $request->query('keys', []), 'is_string'), 0, 20);

        return response()->json([
            'now' => time(),
            'jobs' => array_values(array_filter(array_map(fn ($key) => TranslationJobs::get($key), $keys))),
        ]);
    }

    /**
     * Bir kaydın bir dildeki çevirisi: solda İngilizcesi, sağda düzenlenebilir hali.
     */
    public function edit(string $type, int $id, string $locale)
    {
        [$model, $language] = $this->resolve($type, $id, $locale);

        return view('admin.translations.edit', [
            'type' => $type,
            'model' => $model,
            'language' => $language,
            'fields' => $this->translator->fields($model, $locale),
            'status' => $this->translator->status($model),
            'languages' => Language::targets(),
            'owner' => ModelTranslator::owner($type, $model),
        ]);
    }

    /**
     * Elle düzeltilen çeviriyi kaydeder; bu dil güncel sayılır.
     */
    public function update(Request $request, string $type, int $id, string $locale)
    {
        [$model] = $this->resolve($type, $id, $locale);
        $fields = $this->translator->fields($model, $locale);

        $data = $request->validate(collect($fields)->mapWithKeys(fn ($field, $name) => [
            "fields.{$name}" => ['nullable', 'string', $field['html'] ? 'max:200000' : 'max:5000'],
        ])->all())['fields'] ?? [];

        foreach ($fields as $name => $field) {
            $value = $data[$name] ?? null;
            $value = match (true) {
                $field['html'] => HtmlSanitizer::clean($value),
                is_array($field['source']) => array_values(array_filter(array_map('trim', explode(',', (string) $value)))),
                default => filled($value) ? trim($value) : null,
            };

            filled($value) ? $model->setTranslation($name, $locale, $value) : $model->forgetTranslation($name, $locale);
        }

        $model->save();
        $this->translator->markCurrent($model, $locale);

        return back()->with('success', __(':language çevirisi kaydedildi.', ['language' => Language::where('code', $locale)->value('native_name')]));
    }

    /**
     * @return array{0: Model, 1: Language}
     */
    private function resolve(string $type, int $id, string $locale): array
    {
        $model = (ModelTranslator::TYPES[$type] ?? abort(404))::findOrFail($id);
        $language = Language::active()->where('code', $locale)->where('code', '!=', Language::SOURCE)->firstOrFail();

        return [$model, $language];
    }
}
