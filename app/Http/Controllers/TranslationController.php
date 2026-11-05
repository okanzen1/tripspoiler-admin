<?php

namespace App\Http\Controllers;

use App\Jobs\TranslateModel;
use App\Models\Language;
use App\Models\TranslationState;
use App\Services\Translation\ModelTranslator;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

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

        // Tür başına kayıt sayısı ve dil başına çevirisi eksik/eskimiş kayıt sayısı
        $types = [];
        $pendingByLocale = [];
        foreach (ModelTranslator::TYPES as $type => $class) {
            $states = TranslationState::where('translatable_type', (new $class)->getMorphClass())->get()->groupBy('translatable_id');
            $ok = 0;
            $items = $class::all();
            foreach ($items as $item) {
                $status = $this->translator->status($item, $states->get($item->getKey(), collect())->pluck('source_hash', 'locale'));
                $ok += collect($status)->every(fn ($s) => $s === 'ok') ? 1 : 0;
                foreach ($status as $locale => $state) {
                    $pendingByLocale[$locale] = ($pendingByLocale[$locale] ?? 0) + ($state === 'ok' ? 0 : 1);
                }
            }
            $types[$type] = ['total' => $items->count(), 'ok' => $ok];
        }

        return view('admin.translations.index', [
            'languages' => $languages,
            'types' => $types,
            'pendingByLocale' => $pendingByLocale,
            'queued' => DB::table('jobs')->where('payload', 'like', '%TranslateModel%')->count(),
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
     * Çevirisi eksik ya da eskimiş kayıtları sıraya alır: tüm diller ya da tek dil (locale).
     */
    public function translateAll(Request $request)
    {
        $locale = $request->validate(['locale' => ['nullable', Rule::in(Language::targets()->pluck('code'))]])['locale'] ?? null;

        $count = 0;
        foreach (ModelTranslator::TYPES as $class) {
            foreach ($class::all() as $item) {
                $pending = $this->translator->pending($item);
                $locales = $locale ? array_values(array_intersect($pending, [$locale])) : $pending;
                if ($locales) {
                    TranslateModel::dispatch($item, $locales);
                    $count++;
                }
            }
        }

        return back()->with('success', $count
            ? __(':count kayıt çeviri sırasına alındı; arka planda tamamlanır.', ['count' => $count])
            : __('Tüm çeviriler zaten güncel.'));
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

        set_time_limit(600); // tüm diller birkaç dakika sürebilir
        try {
            $done = $this->translator->translate($model, $locales);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', __('Çeviri yapılamadı: :error', ['error' => $e->getMessage()]));
        }

        $names = Language::whereIn('code', $done)->pluck('native_name')->implode(', ');

        return back()->with('success', $done
            ? __('Çevrildi: :languages', ['languages' => $names])
            : __('Tüm çeviriler zaten güncel.'));
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
