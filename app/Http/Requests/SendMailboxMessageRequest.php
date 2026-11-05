<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Kurum e-posta hesabından gönderim (yeni, yanıt, tümünü yanıtla, ilet). Alıcılar virgül veya noktalı virgülle ayrılır.
 */
class SendMailboxMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // E-postayı tüm panel kullanıcıları (admin + editör) gönderebilir
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $addresses = fn (bool $required) => [$required ? 'required' : 'nullable', 'string', 'max:2000', function (string $attribute, mixed $value, Closure $fail) {
            foreach ($this->split((string) $value) as $address) {
                if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
                    $fail(__("“:address” geçerli bir e-posta adresi değil.", ['address' => $address]));

                    return;
                }
            }
        }];

        return [
            'mode' => ['required', 'in:new,reply,reply_all,forward'],
            'original_folder' => ['nullable', 'required_with:original_uid', 'string', 'max:200'],
            'original_uid' => ['nullable', 'integer', 'min:1'],
            'to' => $addresses(true),
            'cc' => $addresses(false),
            'bcc' => $addresses(false),
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:'.config('mailbox.attachment_max_kb')],
            'include_attachments' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['to' => __('alıcı'), 'cc' => __('bilgi (CC)'), 'bcc' => __('gizli bilgi (BCC)'), 'subject' => __('konu'), 'body' => __('mesaj'), 'attachments.*' => __('ek dosya')];
    }

    /**
     * @return list<string>
     */
    public function addresses(string $field): array
    {
        return $this->split((string) $this->validated($field));
    }

    /**
     * @return list<array{name: string, mime: string, content: string}>
     */
    public function uploadedFiles(): array
    {
        return array_values(array_map(fn (UploadedFile $file) => [
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'content' => (string) $file->get(),
        ], $this->file('attachments', [])));
    }

    /**
     * @return list<string>
     */
    private function split(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[,;]+/', $value) ?: []))));
    }
}
