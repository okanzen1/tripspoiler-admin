<?php

namespace App\Http\Controllers;

use App\Models\Translator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TranslatorController extends Controller
{
    public function index()
    {
        $deeplKey = env('DEEPL_API_KEY');

        // DeepL usage
        $usage = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key '.$deeplKey
        ])->get('https://api-free.deepl.com/v2/usage')->json();

        $used = $usage['character_count'] ?? 0;
        $limit = $usage['character_limit'] ?? 0;
        $remaining = $limit - $used;

        // DeepL languages
        $deeplLanguages = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key '.$deeplKey
        ])->get('https://api-free.deepl.com/v2/languages')->json();

        // DB languages
        $languages = Translator::orderBy('name')->get();

        return view('admin.translations.index', compact(
            'used',
            'limit',
            'remaining',
            'languages',
            'deeplLanguages'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|max:5'
        ]);

        $code = strtoupper($request->code);

        $deeplKey = env('DEEPL_API_KEY');

        $deeplLanguages = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key '.$deeplKey
        ])->get('https://api-free.deepl.com/v2/languages')->json();

        $name = collect($deeplLanguages)
            ->firstWhere('language', $code)['name'] ?? $code;

        Translator::firstOrCreate(
            ['code' => strtolower($code)],
            [
                'name' => $name,
                'active' => true
            ]
        );

        return back()->with('success','Dil eklendi.');
    }

    public function toggle(Translator $translator)
    {
        $translator->update([
            'active' => !$translator->active
        ]);

        return back()->with('success','Dil durumu güncellendi.');
    }

    public function destroy(Translator $translator)
    {
        $translator->delete();

        return back()->with('success','Dil silindi.');
    }
}