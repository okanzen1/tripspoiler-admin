<?php

namespace App\Http\Controllers;

use App\Models\MailTemplate;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * E-posta: hazır yanıtlar ve imza.
 */
class MailTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.mailbox.templates', [
            'templates' => MailTemplate::orderBy('sort_order')->orderBy('title')->get(),
            'signature' => (string) Setting::get('mailbox_signature'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MailTemplate::create($this->validated($request));

        return back()->with('success', __('Hazır yanıt eklendi.'));
    }

    public function update(Request $request, MailTemplate $mailTemplate): RedirectResponse
    {
        $mailTemplate->update($this->validated($request));

        return back()->with('success', __('Hazır yanıt güncellendi.'));
    }

    public function destroy(MailTemplate $mailTemplate): RedirectResponse
    {
        $mailTemplate->delete();

        return back()->with('success', __('Hazır yanıt silindi.'));
    }

    public function updateSignature(Request $request): RedirectResponse
    {
        $data = $request->validate(['signature' => ['nullable', 'string', 'max:2000']], attributes: ['signature' => 'imza']);

        Setting::put('mailbox_signature', $data['signature'] ?? null);

        return back()->with('success', __('İmza kaydedildi.'));
    }

    /**
     * @return array{title: string, body: string, sort_order?: int}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:10000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ], attributes: ['title' => __('başlık'), 'body' => __('metin'), 'sort_order' => __('sıra')]);

        return ['sort_order' => (int) ($data['sort_order'] ?? 0)] + $data;
    }
}
