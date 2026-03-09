@extends('layouts.admin')

@section('title', 'FAQ Düzenle')

@section('content')

    @if (session('success'))
        <div class="alert alert-success small">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger small">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('faqs.update', $faq) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Soru</label>
                    <div class="input-group">

                        <input id="question_en" name="question"
                            value="{{ old('question', $faq->getTranslation('question', 'en')) }}" class="form-control">

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('question','question_en',translations.question)">
                            🌍
                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Cevap</label>
                    <div class="input-group">

                        <textarea id="answer_en" name="answer" class="form-control" rows="5">{{ old('answer', $faq->getTranslation('answer', 'en')) }}</textarea>

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('answer','answer_en',translations.answer)">
                            🌍
                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Kaynak</label>

                    <select class="form-select" disabled>
                        @foreach ($sources as $key => $label)
                            <option value="{{ $key }}" @selected($faq->source === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <input type="hidden" name="source" value="{{ $faq->source }}">
                </div>

                <div class="mb-3">
                    @if ($faq->source === 'activity-show')
                        <label>Kaynak</label>
                        <select name="source_id" class="form-select source-select">
                            <option value="">Activity seç</option>

                            @foreach ($activities as $activity)
                                <option value="{{ $activity->id }}" @selected($faq->source_id == $activity->id)>
                                    {{ $activity->id }} - {{ $activity->name }}
                                </option>
                            @endforeach

                        </select>
                    @elseif ($faq->source === 'blog-show')
                        <label>Kaynak</label>
                        <select name="source_id" class="form-select source-select">
                            <option value="">Blog seç</option>

                            @foreach ($blogs as $blog)
                                <option value="{{ $blog->id }}" @selected($faq->source_id == $blog->id)>
                                    {{ $blog->id }} - {{ $blog->title }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                </div>

                <div class="mb-3">
                    <label>Sıra</label>
                    <input name="sort_order" value="{{ $faq->sort_order }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="status" class="form-select">
                        <option value="1" @selected($faq->status)>Aktif</option>
                        <option value="0" @selected(!$faq->status)>Pasif</option>
                    </select>
                </div>

                <div class="text-end">
                    <button class="btn btn-primary">Kaydet</button>
                </div>
            </form>

        </div>
    </div>

@endsection
@section('scripts')

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const languages = ['en', ...@json($languages)];
        const translations = {
            question: @json($faq->getTranslations('question')),
            answer: @json($faq->getTranslations('answer'))
        };

        function saveTranslation(field, lang, text) {

            fetch("{{ route('admin.saveFaqTranslation') }}", {

                    method: "POST",

                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },

                    body: JSON.stringify({

                        faq_id: {{ $faq->id }},
                        field: field,
                        lang: lang,
                        text: text

                    })

                })
                .then(res => res.json())
                .then(data => {

                    if (data.success) {

                        if (!translations[field]) {
                            translations[field] = {};
                        }

                        translations[field][lang] = text;

                        Swal.fire({
                            icon: "success",
                            title: "Saved"
                        });

                    }

                });

        }

        function openTranslationModal(field, sourceInputId, existingTranslations) {

            let targetOptions = '';

            languages.forEach(lang => {
                if (lang !== 'en') {
                    targetOptions += `<option value="${lang}">${lang.toUpperCase()}</option>`;
                }
            });

            const sourceText = document.getElementById(sourceInputId).value ?? '';

            Swal.fire({

                title: "Translation",
                width: 700,
                showConfirmButton: false,

                html: `
                    <div>

                        <label>Source</label>
                        <textarea id="source_text" class="form-control" disabled></textarea>

                        <label class="mt-3">Language</label>
                        <select id="translation_lang" class="form-select">
                        ${targetOptions}
                        </select>

                        <label class="mt-3">Translation</label>
                        <textarea id="translation_text" class="form-control"></textarea>

                        <div class="mt-3 d-flex justify-content-between">

                            <button id="translate_btn" class="btn btn-primary">
                            Translate
                            </button>

                            <div>
                            <button id="cancel_btn" class="btn btn-secondary me-2">
                            Cancel
                            </button>

                            <button id="save_btn" class="btn btn-success">
                            Save
                            </button>
                            </div>

                        </div>

                    </div>
                `,

                didOpen: () => {

                    document.getElementById('source_text').value = sourceText;

                    const langSelect = document.getElementById('translation_lang');
                    const textInput = document.getElementById('translation_text');
                    const translateBtn = document.getElementById('translate_btn');

                    function loadExisting() {
                        const lang = langSelect.value;
                        textInput.value = existingTranslations?.[lang] ?? '';
                    }

                    loadExisting();

                    langSelect.addEventListener('change', loadExisting);

                    translateBtn.onclick = async () => {

                        const lang = langSelect.value;

                        translateBtn.disabled = true;

                        const res = await fetch("/admin/translate", {

                            method: "POST",

                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },

                            body: JSON.stringify({
                                text: sourceText,
                                lang: lang
                            })

                        });

                        const data = await res.json();

                        let result = data.translation ?? '';

                        textInput.value = result;

                        translateBtn.disabled = false;

                    };

                    document.getElementById('save_btn').onclick = () => {

                        const lang = langSelect.value;
                        const text = textInput.value;

                        saveTranslation(field, lang, text);

                        Swal.close();

                    };

                    document.getElementById('cancel_btn').onclick = () => Swal.close();

                }

            });

        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            $('.source-select').select2({
                placeholder: "Arama yap...",
                allowClear: true,
                width: '100%'
            });

        });
    </script>
@endsection
