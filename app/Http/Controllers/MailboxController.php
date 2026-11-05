<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMailboxMessageRequest;
use App\Models\BlogSubscriber;
use App\Models\MailTemplate;
use App\Models\Setting;
use App\Services\Mailbox\InboxUnreadCount;
use App\Services\Mailbox\Mailbox;
use App\Services\Mailbox\MailboxBodyRenderer;
use App\Services\Mailbox\MailboxSender;
use App\Services\Mailbox\MailboxUnavailable;
use App\Services\Mailbox\OutgoingMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

/**
 * Kurum e-posta hesabı (info@tripspoiler.com). Webmail'deki işlemlerin aynısı: klasörler, okuma, okundu/yıldız,
 * taşıma, silme, yeni e-posta, yanıtla, tümünü yanıtla, ilet. E-postalar panelde saklanmaz; her işlem hesapta yapılır.
 */
class MailboxController extends Controller
{
    public const DEFAULT_FOLDER = 'INBOX';

    public function __construct(private Mailbox $mailbox) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'folder' => ['nullable', 'string', 'max:200'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $folder = $filters['folder'] ?? self::DEFAULT_FOLDER;
        $perPage = (int) config('mailbox.per_page');

        try {
            $folders = $this->mailbox->folders();
            InboxUnreadCount::remember($folders);
            $result = $this->mailbox->messages($folder, (int) ($filters['page'] ?? 1), $perPage, $filters['q'] ?? null);
        } catch (Throwable $exception) {
            return $this->unavailable($exception);
        }

        return view('admin.mailbox.index', [
            'folders' => $folders,
            'folder' => $folder,
            'current' => collect($folders)->firstWhere('path', $folder),
            'filters' => $filters,
            'subscriberEmails' => $this->subscriberEmails(collect($result['items'])->pluck('from.email')->filter()->all()),
            'messages' => new LengthAwarePaginator($result['items'], $result['total'], $perPage, (int) ($filters['page'] ?? 1), [
                'path' => route('mailbox.index'),
                'query' => array_filter(['folder' => $folder, 'q' => $filters['q'] ?? null]),
            ]),
        ]);
    }

    public function show(Request $request): View|RedirectResponse
    {
        [$folder, $uid] = $this->target($request);

        try {
            $message = $this->mailbox->message($folder, $uid);
            $folders = $this->mailbox->folders();
            InboxUnreadCount::remember($folders);
        } catch (Throwable $exception) {
            return $this->unavailable($exception);
        }

        if ($message === null) {
            return redirect()->route('mailbox.index', ['folder' => $folder])->with('error', __('E-posta bulunamadı; taşınmış veya silinmiş olabilir.'));
        }

        return view('admin.mailbox.show', [
            'folder' => $folder,
            'folders' => $folders,
            'current' => collect($folders)->firstWhere('path', $folder),
            'message' => $message,
            'isSubscriber' => ($email = $message['from']['email'] ?? null) && $this->subscriberEmails([$email]) !== [],
            'hasRemoteImages' => filled($message['html']) && preg_match('#<img[^>]+src\s*=\s*["\']?https?://#i', (string) $message['html']) === 1,
        ]);
    }

    /**
     * E-posta gövdesi (iframe içeriği): betik çalıştıramayan, uzak görselleri varsayılan olarak engelleyen belge.
     */
    public function body(Request $request, MailboxBodyRenderer $renderer): Response
    {
        [$folder, $uid] = $this->target($request);

        try {
            $message = $this->mailbox->message($folder, $uid);
        } catch (Throwable $exception) {
            Log::warning('E-posta gövdesi alınamadı.', ['error' => $exception->getMessage()]);
            abort(503);
        }

        abort_if($message === null, 404);

        $nonce = Str::random(32);
        $html = $renderer->document($message, fn (int $index) => route('mailbox.attachment', ['folder' => $folder, 'uid' => $uid, 'index' => $index, 'inline' => 1]), $nonce);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => $renderer->contentSecurityPolicy($request->boolean('images'), $nonce),
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function attachment(Request $request): HttpResponse
    {
        [$folder, $uid] = $this->target($request);
        $index = (int) $request->validate(['index' => ['required', 'integer', 'min:0']])['index'];

        try {
            $file = $this->mailbox->attachment($folder, $uid, $index);
        } catch (Throwable $exception) {
            Log::warning('E-posta eki alınamadı.', ['error' => $exception->getMessage()]);
            abort(503);
        }

        abort_if($file === null, 404);

        // Gömülü görseller (inline) yalnızca görsel türündeyse sayfada gösterilir; diğer her şey indirilir
        $inline = $request->boolean('inline') && str_starts_with($file['mime'], 'image/') && $file['mime'] !== 'image/svg+xml';

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . Str::ascii($file['name']) . '"; filename*=UTF-8\'\'' . rawurlencode($file['name']),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Yeni e-posta, yanıtla, tümünü yanıtla veya ilet formu.
     */
    public function compose(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['nullable', 'in:new,reply,reply_all,forward'],
            'folder' => ['nullable', 'string', 'max:200'],
            'uid' => ['nullable', 'integer', 'min:1'],
            'to' => ['nullable', 'email'],
        ]);
        $mode = $data['mode'] ?? 'new';
        $original = null;

        if ($mode !== 'new') {
            try {
                $original = isset($data['folder'], $data['uid']) ? $this->mailbox->message($data['folder'], (int) $data['uid']) : null;
            } catch (Throwable $exception) {
                return $this->unavailable($exception);
            }

            abort_if($original === null, 404);
        }

        return view('admin.mailbox.compose', [
            'mode' => $mode,
            'original' => $original,
            'folder' => $data['folder'] ?? null,
            'draft' => $this->draft($mode, $original, $data['to'] ?? null),
            'templates' => MailTemplate::orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'body']),
        ]);
    }

    public function send(SendMailboxMessageRequest $request, MailboxSender $sender): RedirectResponse
    {
        $original = null;

        if ($request->filled('original_uid')) {
            try {
                $original = $this->mailbox->message((string) $request->validated('original_folder'), (int) $request->validated('original_uid'));
            } catch (Throwable $exception) {
                return $this->unavailable($exception);
            }
        }

        $files = $request->uploadedFiles();

        // İletmede orijinal e-postanın ekleri de gönderilir
        if ($original !== null && $request->validated('mode') === 'forward' && $request->boolean('include_attachments')) {
            foreach ($original['attachments'] as $attachment) {
                $file = $this->mailbox->attachment((string) $request->validated('original_folder'), (int) $request->validated('original_uid'), $attachment['index']);

                if ($file !== null) {
                    $files[] = $file;
                }
            }
        }

        $isReply = $original !== null && in_array($request->validated('mode'), ['reply', 'reply_all'], true);

        try {
            $sender->send(new OutgoingMail(
                toAddresses: $request->addresses('to'),
                ccAddresses: $request->addresses('cc'),
                bccAddresses: $request->addresses('bcc'),
                subjectLine: $request->validated('subject'),
                bodyText: $request->validated('body'),
                files: $files,
                inReplyTo: $isReply ? $original['message_id'] : null,
                references: $isReply ? trim(($original['references'] ?? '') . ' ' . ($original['message_id'] ?? '')) : null,
            ));

            if ($isReply) {
                $this->mailbox->setAnswered((string) $request->validated('original_folder'), (int) $request->validated('original_uid'));
            }
        } catch (MailboxUnavailable) {
            return back()->withInput()->with('error', __('E-posta gönderilemedi. E-posta sunucusuna ulaşılamadı veya hesap ayarları hatalı; biraz sonra tekrar deneyin.'));
        }

        return redirect()->route('mailbox.index', ['folder' => $request->validated('original_folder') ?: self::DEFAULT_FOLDER])->with('success', __('E-posta gönderildi.'));
    }

    /**
     * Okundu / okunmadı (tek veya seçili e-postalar).
     */
    public function seen(Request $request): RedirectResponse
    {
        [$folder, $uids] = $this->targets($request);
        $seen = $request->boolean('seen');

        return $this->act(fn () => $this->mailbox->setSeen($folder, $uids, $seen), __($seen ? 'Okundu olarak işaretlendi.' : 'Okunmadı olarak işaretlendi.'));
    }

    public function flag(Request $request): RedirectResponse
    {
        [$folder, $uid] = $this->target($request);
        $flagged = $request->boolean('flagged');

        return $this->act(fn () => $this->mailbox->setFlagged($folder, $uid, $flagged), __($flagged ? 'Yıldızlandı.' : 'Yıldız kaldırıldı.'));
    }

    /**
     * Klasöre taşıma (Çöp kutusu, Gereksiz, Gelen kutusu veya kullanıcı klasörü).
     */
    public function move(Request $request): RedirectResponse
    {
        [$folder, $uids] = $this->targets($request);
        $target = (string) $request->validate(['target' => ['required', 'string', 'max:200']])['target'];

        return $this->act(
            fn () => $this->mailbox->move($folder, $uids, $target),
            count($uids) > 1 ? __(':count e-posta taşındı.', ['count' => count($uids)]) : __('E-posta taşındı.'),
            $folder,
        );
    }

    /**
     * Silme: Çöp kutusu dışındaki klasörlerden önce Çöp kutusuna taşınır; Çöp kutusunda kalıcı silinir.
     */
    public function destroy(Request $request): RedirectResponse
    {
        [$folder, $uids] = $this->targets($request);

        try {
            $trash = collect($this->mailbox->folders())->firstWhere('role', 'trash');
        } catch (Throwable $exception) {
            return $this->unavailable($exception);
        }

        if ($trash !== null && $folder !== $trash['path']) {
            return $this->act(fn () => $this->mailbox->move($folder, $uids, $trash['path']), __('E-posta Çöp kutusuna taşındı.'), $folder);
        }

        return $this->act(
            fn () => $this->mailbox->delete($folder, $uids),
            count($uids) > 1 ? __(':count e-posta kalıcı olarak silindi.', ['count' => count($uids)]) : __('E-posta kalıcı olarak silindi.'),
            $folder,
        );
    }

    /**
     * Blog abonesi olan gönderen adresleri (küçük harf). E-postalar şifreli olduğu için hash ile eşleştirilir.
     *
     * @param  list<string>  $emails
     * @return list<string>
     */
    private function subscriberEmails(array $emails): array
    {
        $byHash = collect($emails)->mapWithKeys(fn (string $email) => [BlogSubscriber::hash($email) => mb_strtolower(trim($email))]);

        if ($byHash->isEmpty()) {
            return [];
        }

        return BlogSubscriber::whereIn('email_hash', $byHash->keys())->pluck('email_hash')
            ->map(fn (string $hash) => $byHash[$hash])->unique()->values()->all();
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function target(Request $request): array
    {
        $data = $request->validate(['folder' => ['required', 'string', 'max:200'], 'uid' => ['required', 'integer', 'min:1']]);

        return [$data['folder'], (int) $data['uid']];
    }

    /**
     * @return array{0: string, 1: list<int>}
     */
    private function targets(Request $request): array
    {
        $data = $request->validate([
            'folder' => ['required', 'string', 'max:200'],
            'uids' => ['required', 'array', 'min:1', 'max:100'],
            'uids.*' => ['integer', 'min:1'],
        ], ['uids.required' => __('Önce en az bir e-posta seç.')]);

        return [$data['folder'], array_values(array_unique(array_map('intval', $data['uids'])))];
    }

    private function act(callable $action, string $message, ?string $redirectFolder = null): RedirectResponse
    {
        try {
            $action();
        } catch (Throwable $exception) {
            Log::warning('E-posta işlemi yapılamadı.', ['error' => $exception->getMessage()]);

            return back()->with('error', __('İşlem yapılamadı. E-posta sunucusuna ulaşılamadı; tekrar dene.'));
        }

        return $redirectFolder !== null
            ? redirect()->route('mailbox.index', ['folder' => $redirectFolder])->with('success', $message)
            : back()->with('success', $message);
    }

    private function unavailable(Throwable $exception): View
    {
        Log::warning('E-posta sunucusuna bağlanılamadı.', ['error' => $exception->getMessage()]);

        return view('admin.mailbox.unavailable', ['configured' => filled(config('mailbox.username')) && filled(config('mailbox.password'))]);
    }

    /**
     * Form başlangıç değerleri: yanıtta alıcı/konu/alıntı, iletmede konu ve iletilen e-postanın özeti.
     *
     * @param  array<string, mixed>|null  $original
     * @return array{to: string, cc: string, subject: string, body: string}
     */
    private function draft(string $mode, ?array $original, ?string $to): array
    {
        // İmza yazılacak metnin altına, alıntının üstüne eklenir
        $signature = trim((string) Setting::get('mailbox_signature'));
        $signature = $signature === '' ? '' : "\n\n-- \n" . $signature;

        if ($original === null) {
            return ['to' => (string) $to, 'cc' => '', 'subject' => '', 'body' => $signature];
        }

        $own = mb_strtolower((string) config('mailbox.username'));
        $from = $original['from']['email'] ?? '';
        $replyTo = collect($original['reply_to'])->pluck('email')->filter()->all() ?: array_filter([$from]);
        $others = collect([...$original['to'], ...$original['cc']])->pluck('email')
            ->reject(fn (?string $email) => blank($email) || mb_strtolower($email) === $own || in_array($email, $replyTo, true))
            ->unique()->values()->all();

        $when = $original['date']?->locale(app()->getLocale())->translatedFormat('j F Y H:i');
        $sender = trim(($original['from']['name'] ?? '') . ' <' . $from . '>');
        $plain = $original['text'] ?: trim(html_entity_decode(strip_tags(preg_replace('#<(br|/p|/div)[^>]*>#i', "\n", (string) $original['html']) ?? ''), ENT_QUOTES | ENT_HTML5));
        $subject = (string) $original['subject'];

        return match ($mode) {
            'forward' => [
                'to' => '',
                'cc' => '',
                'subject' => preg_match('/^(fwd?|ilt):/i', $subject) ? $subject : 'İlt: ' . $subject,
                'body' => $signature . "\n\n---------- İletilen e-posta ----------\nGönderen: {$sender}\nTarih: {$when}\nKonu: {$subject}\n\n" . $plain,
            ],
            default => [
                'to' => implode(', ', $replyTo),
                'cc' => $mode === 'reply_all' ? implode(', ', $others) : '',
                'subject' => preg_match('/^(re|ynt):/i', $subject) ? $subject : 'Ynt: ' . $subject,
                'body' => $signature . "\n\n{$when} tarihinde {$sender} yazdı:\n" . collect(preg_split('/\R/', $plain))->map(fn (string $line) => '> ' . $line)->implode("\n"),
            ],
        };
    }
}
