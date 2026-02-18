<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{

    public const SOURCES = [
        'activity' => 'Activity',
        'blog'     => 'Blog',
        'pass'     => 'Pass',
        'general'  => 'General',
        'home' => 'Home',
        'activity-show' => 'Activity Show',
    ];


    public function index(Request $request)
    {
        $query = Faq::query();

        // Soru arama
        if ($request->filled('search')) {
            $query->where('question', 'like', '%' . $request->search . '%');
        }

        // Status filtre
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Source filtre
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        } else {
            $query->where('source', 'home');
        }

        // Source ID filtre
        if ($request->filled('source_id')) {
            $query->where('source_id', $request->source_id);
        }

        $faqs = $query
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.faqs.index', [
            'faqs' => $faqs,
            'sources' => self::SOURCES,
        ]);
    }

    public function create()
    {
        return view('admin.faqs.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar ekleyemez.');
        }

        $data = $request->validate([
            'question' => 'required|string|max:255',
        ]);

        $faq = Faq::create([
            'question' => $data['question'],
            'answer' => $data['answer'] ?? null,
            'source' => $data['source'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? false,
        ]);

        return redirect()
            ->route('faqs.edit', $faq)
            ->with('success', 'FAQ oluşturuldu');
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.edit', [
            'faq' => $faq,
            'sources' => self::SOURCES,
        ]);
    }

    public function update(Request $request, Faq $faq)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $data = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'source' => 'nullable|string',
            'source_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'status' => 'boolean',
        ]);

        $faq->update([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'source' => $data['source'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? true,
        ]);

        return redirect()
            ->route('faqs.index', $faq)
            ->with('success', 'FAQ güncellendi');
    }

    public function destroy(Faq $faq)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar silemez.');
        }

        $faq->delete();

        return redirect()
            ->route('faqs.index')
            ->with('success', 'FAQ silindi');
    }
}
