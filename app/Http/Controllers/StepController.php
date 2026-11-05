<?php

namespace App\Http\Controllers;

use App\Models\SectionText;
use App\Models\Step;
use App\Support\FeatureIcons;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Anasayfadaki "Nasıl çalışır" bölümü: başlık yazıları (section_texts, key "steps") ve adımlar.
 */
class StepController extends Controller
{
    /** Bağlantı için hazır seçenekler: site içi adres → panelde görünen ad */
    public const LINKS = [
        '/activities' => 'Aktiviteler sayfası',
        '/cities' => 'Şehirler sayfası',
        '/blog' => 'Blog sayfası',
        '/reviews' => 'Yorumlar sayfası',
        '/about' => 'Hakkımızda sayfası',
        '/contact' => 'İletişim sayfası',
    ];

    public function index()
    {
        return view('admin.steps.index', [
            'section' => SectionText::for('steps'),
            'steps' => Step::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function updateSection(Request $request)
    {
        $data = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:60'],
            'title_highlight' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:200'],
        ], [], ['title' => __('başlık')]);

        $section = SectionText::for('steps');
        foreach ($section->translatable as $field) {
            filled($data[$field] ?? null) ? $section->setTranslation($field, 'en', trim($data[$field])) : $section->forgetTranslation($field, 'en');
        }
        $section->save();

        return back()->with('success', __('Bölüm yazıları kaydedildi.'));
    }

    public function create()
    {
        return view('admin.steps.form', ['step' => new Step(['active' => true, 'icon' => 'sparkles', 'link' => '/activities'])]);
    }

    public function store(Request $request)
    {
        $step = new Step();
        $this->fill($step, $request);
        $step->sort_order = (int) Step::max('sort_order') + 1;
        $step->save();

        return redirect()->route('steps.index')->with('success', __('Adım eklendi.'));
    }

    public function edit(Step $step)
    {
        return view('admin.steps.form', ['step' => $step]);
    }

    public function update(Request $request, Step $step)
    {
        $this->fill($step, $request);
        $step->save();

        return redirect()->route('steps.index')->with('success', __('Adım güncellendi.'));
    }

    public function destroy(Step $step)
    {
        $step->delete();

        return back()->with('success', __('Adım silindi.'));
    }

    public function toggle(Request $request, Step $step)
    {
        $step->update(['active' => !$step->active]);
        $message = __($step->active ? 'Adım yayına alındı.' : 'Adım yayından kaldırıldı.');

        return $request->expectsJson()
            ? response()->json(['active' => $step->active, 'message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Sırayı bir yukarı (-1) ya da aşağı (1) taşır: komşusuyla yer değiştirir.
     */
    public function move(Request $request, Step $step)
    {
        $direction = $request->integer('step') < 0 ? -1 : 1;
        $ordered = Step::orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $ordered->search(fn (Step $item) => $item->is($step));
        $neighbour = $ordered->get($index + $direction);

        if ($neighbour) {
            $current = $ordered[$index];
            $ordered->each(fn (Step $item, int $i) => $item->sort_order = $i + 1);
            [$current->sort_order, $neighbour->sort_order] = [$neighbour->sort_order, $current->sort_order];
            $ordered->each->saveQuietly();
        }

        return back();
    }

    /**
     * Formdaki alanlar: yazılar sadece İngilizce yazılır, diğer diller korunur.
     */
    private function fill(Step $step, Request $request): void
    {
        $data = $request->validate([
            'icon' => ['required', Rule::in(array_keys(FeatureIcons::ICONS))],
            'title' => ['required', 'string', 'max:50'],
            'text' => ['nullable', 'string', 'max:180'],
            'cta' => ['nullable', 'string', 'max:40'],
            'link_choice' => ['required', 'string'],
            'link_custom' => ['nullable', 'required_if:link_choice,custom', 'string', 'max:500', 'regex:#^(https?://|/)#i'],
            'active' => ['nullable'],
        ], ['link_custom.regex' => __('Bağlantı https:// ya da / ile başlamalı.'), 'link_custom.required_if' => __('Bağlantı adresini yaz.')],
            ['title' => __('başlık'), 'cta' => __('bağlantı yazısı'), 'link_custom' => __('bağlantı')]);

        $step->icon = $data['icon'];
        $step->link = $data['link_choice'] === 'custom' ? trim($data['link_custom']) : (array_key_exists($data['link_choice'], self::LINKS) ? $data['link_choice'] : null);
        $step->active = $request->boolean('active');

        foreach (['title', 'text', 'cta'] as $field) {
            filled($data[$field] ?? null) ? $step->setTranslation($field, 'en', trim($data[$field])) : $step->forgetTranslation($field, 'en');
        }
    }
}
