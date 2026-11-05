<?php

namespace App\Http\Controllers;

use App\Jobs\SendBulkEmail;
use App\Models\BlogSubscriber;
use App\Models\BulkEmail;
use App\Models\MailTemplate;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Toplu e-posta: blog abonelerine ve/veya elle yazılan adreslere. Her alıcıya ayrı e-posta kuyrukla gider.
 */
class BulkEmailController extends Controller
{
    public function create(): View
    {
        $signature = trim((string) Setting::get('mailbox_signature'));

        return view('admin.mailbox.bulk', [
            'subscribers' => BlogSubscriber::active()->latest('id')->get(),
            'templates' => MailTemplate::orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'body']),
            'body' => $signature === '' ? '' : "\n\n-- \n" . $signature,
            'history' => BulkEmail::with('sender')->latest()->limit(5)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Listede işareti kaldırılan aboneler gönderilmez
            'subscriber_ids' => ['nullable', 'array'],
            'subscriber_ids.*' => ['integer'],
            'extra_emails' => ['nullable', 'string', 'max:10000'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => __('Göndermeden önce alıcı sayısını onayla.')], ['subject' => __('konu'), 'body' => __('mesaj'), 'extra_emails' => __('ek adresler')]);

        $subscribers = BlogSubscriber::active()->whereIn('id', $data['subscriber_ids'] ?? [])->get();
        [$extra, $invalid] = $this->extraAddresses((string) ($data['extra_emails'] ?? ''), $subscribers);

        if ($invalid !== []) {
            return back()->withInput()->withErrors(['extra_emails' => __('Geçersiz e-posta adresi') . ': ' . implode(', ', array_slice($invalid, 0, 5)) . (count($invalid) > 5 ? '…' : '')]);
        }

        if (count($extra) > config('mailbox.extra_limit')) {
            return back()->withInput()->withErrors(['extra_emails' => __('En fazla :count ek adres yazılabilir.', ['count' => config('mailbox.extra_limit')])]);
        }

        $total = $subscribers->count() + count($extra);

        if ($total === 0) {
            return back()->withInput()->with('error', __('Alıcı yok: en az bir abone seç ya da bir adres yaz.'));
        }

        if ($total > config('mailbox.bulk_limit')) {
            return back()->withInput()->with('error', __('Tek seferde en fazla :count kişiye gönderilebilir.', ['count' => config('mailbox.bulk_limit')]));
        }

        foreach ($subscribers as $subscriber) {
            SendBulkEmail::dispatch($data['subject'], $data['body'], subscriberId: $subscriber->id);
        }

        foreach ($extra as $address) {
            SendBulkEmail::dispatch($data['subject'], $data['body'], address: $address);
        }

        BulkEmail::create(['subject' => $data['subject'], 'recipient_count' => $total, 'sent_by' => $request->user()->id]);

        return redirect()->route('mailbox.index')->with('success', __('E-posta :count kişiye gönderilmek üzere sıraya alındı; birkaç dakika içinde gider.', ['count' => $total]));
    }

    /**
     * Elle yazılan adresler (virgül, noktalı virgül, boşluk veya satırla ayrılmış). Zaten abone olarak
     * listede olanlar ikinci kez eklenmez.
     *
     * @return array{0: list<string>, 1: list<string>}
     */
    private function extraAddresses(string $input, $subscribers): array
    {
        $alreadyIncluded = $subscribers->pluck('email_hash')->flip();
        $valid = [];
        $invalid = [];

        foreach (preg_split('/[\s,;]+/', $input, -1, PREG_SPLIT_NO_EMPTY) as $address) {
            $address = mb_strtolower(trim($address));

            if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
                $invalid[] = $address;
            } elseif (!isset($alreadyIncluded[BlogSubscriber::hash($address)])) {
                $valid[$address] = true;
            }
        }

        return [array_keys($valid), $invalid];
    }
}
