<?php

namespace App\Http\Controllers;

use App\Models\Redirect;
use Illuminate\Http\Request;

/**
 * Yönetim → Yönlendirmeler: eski adres → yeni adres (301). Slug değişince otomatik eklenir, elle de eklenebilir.
 */
class RedirectController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $locale = \App\Models\Language::where('code', $request->get('locale'))->value('code');

        $redirects = Redirect::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('from_path', 'like', "%{$search}%")->orWhere('to_path', 'like', "%{$search}%")))
            ->when($locale, fn ($q) => $q->where('from_path', 'like', "/{$locale}/%"))
            ->orderByDesc('hits')->orderByDesc('id')
            ->paginate(50)->withQueryString();

        return view('admin.redirects.index', [
            'redirects' => $redirects,
            'search' => $search,
            'locale' => $locale,
            'languages' => \App\Models\Language::ordered()->get()->mapWithKeys(fn ($l) => [$l->code => $l->flag() . ' ' . $l->native_name])->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:700', 'different:to_path'],
            'to_path' => ['required', 'string', 'max:700'],
        ], attributes: ['from_path' => __('eski adres'), 'to_path' => __('yeni adres')]);

        Redirect::point($data['from_path'], $data['to_path']);

        return back()->with('success', __('Yönlendirme eklendi.'));
    }

    public function destroy(Redirect $redirect)
    {
        $redirect->delete();

        return back()->with('success', __('Yönlendirme silindi.'));
    }
}
