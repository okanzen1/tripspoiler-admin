@extends('layouts.admin')

@section('title', 'Blog İçeriği Düzenle')

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger small">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="contentForm" method="POST" action="{{ route('blogs.content.update', [$blog, $content]) }}">
        @csrf
        @method('PUT')

        <div class="card mb-5">
            <div class="card-header"><strong>Ana İçerik</strong></div>
            <div class="card-body">

                <div class="mb-3">
                    <label>Blog Adı</label>
                    <div class="input-group">

                        <input id="title_en" type="text" name="title" class="form-control"
                            value="{{ old('title', $content->getTranslation('title', 'en')) }}" required>

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('title','title_en',translations.title)">
                            🌍
                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="status" class="form-select">
                        <option value="1" @selected($content->status)>Aktif</option>
                        <option value="0" @selected(!$content->status)>Pasif</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Sıralama</label>
                    <input type="number" name="sort_order" class="form-control"
                        value="{{ old('sort_order', $content->sort_order) }}">
                </div>
            </div>
        </div>

        {{-- ANA İÇERİK --}}
        <div class="card mb-5">
            <div class="card-header"><strong>Ana İçerik</strong></div>
            <div class="card-body">

                <div id="editor" style="min-height: 400px;">
                    {!! old('content', $content->getTranslation('content', 'en')) !!}
                </div>

                <input type="hidden" name="content" id="contentInput">

                <div class="mt-2 text-end">
                    <button type="button" class="btn btn-outline-primary btn-sm"
                        onclick="openTranslationModal('content','contentInput',translations.content)">
                        🌍
                    </button>
                </div>
            </div>
        </div>

        <button type="button" id="saveBtn" class="btn btn-primary"
            style="position: fixed; bottom: 30px; right: 30px; z-index: 1050;">
            Kaydet
        </button>
    </form>

@endsection

@section('scripts')

    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        const languages = ['en', ...@json($languages)];
        const translations = {
            title: @json($content->getTranslations('title')),
            content: @json($content->getTranslations('content'))
        };
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'İçeriği buraya yaz...',
                modules: {
                    toolbar: {
                        container: [
                            ['bold', 'italic', 'underline'],
                            [{
                                list: 'ordered'
                            }, {
                                list: 'bullet'
                            }],
                            ['link', 'image'],
                            ['clean']
                        ],
                        handlers: {
                            image: imageHandler
                        }
                    }
                }
            });

            document.getElementById('saveBtn').addEventListener('click', function() {
                const html = quill.root.innerHTML.trim();

                if (html === '' || html === '<p><br></p>') {
                    alert('İçerik boş olamaz');
                    return;
                }

                document.getElementById('contentInput').value = html;
                document.getElementById('contentForm').submit();
            });

            function imageHandler() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.click();

                input.onchange = async () => {
                    const file = input.files[0];
                    if (!file) return;

                    if (!file.type.startsWith('image/')) {
                        alert('Sadece görsel yükleyebilirsin');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('source', 'blog_content');
                    formData.append('source_id', '{{ $content->id }}');

                    try {
                        const response = await fetch('{{ route('images.upload') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const data = await response.json();

                        if (!data.url) throw new Error();

                        const range = quill.getSelection(true);
                        const index = range ? range.index : quill.getLength();

                        quill.insertEmbed(index, 'image', data.url);
                        quill.setSelection(index + 1);

                    } catch (e) {
                        alert('Görsel yüklenirken hata oluştu');
                    }
                };
            }

            quill.clipboard.addMatcher('img', function(node) {
                const src = node.getAttribute('src') || '';
                if (src.startsWith('data:image')) {
                    return new Delta();
                }
                return node;
            });

        });
    </script>

    <script>
        function openTranslationModal(field, sourceInputId, existingTranslations) {

            let targetOptions = '';

            languages.forEach(lang => {
                if (lang !== 'en') {
                    targetOptions += `<option value="${lang}">${lang.toUpperCase()}</option>`;
                }
            });

            let sourceText = '';

            if (sourceInputId === 'contentInput') {
                sourceText = document.querySelector('#editor .ql-editor').innerHTML;
            } else {
                const el = document.getElementById(sourceInputId);
                sourceText = el ? el.value : '';
            }

            Swal.fire({
                title: "Translation",
                width: 700,
                showConfirmButton: false,

                html: `
                <div style="text-align:left">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Source Language</label>
                        <input class="form-control" value="EN (source)" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Source Text</label>
                        <textarea id="source_text" class="form-control" disabled style="min-height:120px;"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Language</label>
                        <select id="translation_lang" class="form-select">
                            ${targetOptions}
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Translation</label>
                        <textarea id="translation_text" class="form-control" style="min-height:160px;"></textarea>
                    </div>

                    <div style="display:flex;justify-content:space-between">
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
                        let existing = existingTranslations?.[lang] ?? '';

                        if (field === 'content' && existing) {
                            textInput.value = existing;
                        } else {
                            textInput.value = existing;
                        }
                    }

                    loadExisting();

                    langSelect.addEventListener('change', loadExisting);

                    translateBtn.addEventListener('click', async () => {

                        const lang = langSelect.value;

                        translateBtn.disabled = true;
                        translateBtn.innerText = "Translating...";

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
                        translateBtn.innerText = "Translate";
                    });

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

        function saveTranslation(field, lang, text) {

            fetch("{{ route('admin.saveBlogContentTranslation') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        content_id: {{ $content->id }},
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
    </script>
@endsection
