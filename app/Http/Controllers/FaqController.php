<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('sort_order')->paginate(10);

        return view('admin.faqs.index', compact('faqs'));
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
            'answer' => 'required|string',
            'source' => 'nullable|string',
            'source_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'status' => 'boolean',
        ]);

        Faq::create([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'source' => $data['source'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? true,
        ]);

        return redirect()
            ->route('faqs.index')
            ->with('success', 'FAQ oluşturuldu');
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.edit', compact('faq'));
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

        return back()->with('success', 'FAQ güncellendi');
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
