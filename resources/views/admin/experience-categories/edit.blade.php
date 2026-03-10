@extends('layouts.admin')

@section('title', 'Kategori Düzenle')

@section('content')
    <div class="container py-4">

        <div class="card">
            <div class="card-body">

                <h5 class="mb-4">Kategori Düzenle</h5>

                <form method="POST" action="{{ route('experience-categories.update', $category) }}" id="categoryForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="fw-bold">Kategori</label>
                        <div class="form-control bg-light">
                            {{ $category->getTranslation('name', app()->getLocale()) }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Sıra</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ $category->sort_order }}">
                    </div>

                    <div class="mb-3">
                        <label>Description</label>
                        @php
                            $desc = $category->descriptions()->first();
                            $content = $desc?->getTranslation('description', app()->getLocale()) ?? '';
                        @endphp

                        <div id="editor" style="min-height: 350px;"></div>
                        <input type="hidden" name="description" id="descriptionInput">
                        <div class="mt-2 text-end">

                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="openTranslationModal('description','descriptionInput',translations.description)">
                                🌍
                            </button>

                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">

                        <button type="button" class="btn btn-danger" id="deleteBtn">
                            Kategoriyi Sil
                        </button>

                        <button type="button" id="saveBtn" class="btn btn-primary">
                            Güncelle
                        </button>

                    </div>
                </form>

                {{-- DELETE FORM (DIŞARIDA) --}}
                <form id="deleteForm" method="POST" action="{{ route('experience-categories.destroy', $category) }}">
                    @csrf
                    @method('DELETE')
                </form>

                </form>

            </div>
        </div>

    </div>
@endsection


@section('scripts')

    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'Açıklamayı buraya yaz...',
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

            // HTML’i düzgün inject et
            const initialContent = @json($content);

            if (initialContent) {
                quill.clipboard.dangerouslyPasteHTML(initialContent);
            }

            // SAVE
            document.getElementById('saveBtn').addEventListener('click', function() {

                const html = quill.root.innerHTML.trim();

                if (html === '' || html === '<p><br></p>') {
                    alert('Description boş olamaz');
                    return;
                }

                document.getElementById('descriptionInput').value = html;
                document.getElementById('categoryForm').submit();
            });

            // IMAGE UPLOAD
            async function imageHandler() {

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
                    formData.append('source', 'city_experience_category_description');
                    formData.append('source_id', '{{ $desc?->id ?? 0 }}');

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

        });
    </script>
    <script>
        document.getElementById('deleteBtn').addEventListener('click', function() {
            if (!confirm('Bu kategori tamamen silinsin mi? Bu işlem geri alınamaz!')) {
                return;
            }
            document.getElementById('deleteForm').submit();
        });
    </script>
    <script>
        const languages = ['en', ...@json($languages)];

        const translations = {
            description: @json($desc?->getTranslations('description') ?? [])
        };

        function openTranslationModal(field, sourceInputId, existingTranslations) {

            let targetOptions = '';

            languages.forEach(lang => {
                if (lang !== 'en') {
                    targetOptions += `<option value="${lang}">${lang.toUpperCase()}</option>`;
                }
            });

            let sourceText = document.querySelector('#editor .ql-editor').innerHTML;

            Swal.fire({
                title: "Çeviri " + field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                width: 700,
                showConfirmButton: false,
                html: `
                    <div>

                        <label>Source</label>
                        <textarea id="source_text" class="form-control" disabled></textarea>

                        <label class="mt-3">Hedef Dil</label>
                        <select id="translation_lang" class="form-select">
                            ${targetOptions}
                        </select>

                        <label class="mt-3">Çeviri</label>
                        <textarea id="translation_text" class="form-control"></textarea>

                        <div class="mt-3 d-flex justify-content-between">

                            <button id="translate_btn" class="btn btn-primary">
                                Çevir
                            </button>

                            <div>
                                <button id="cancel_btn" class="btn btn-danger me-2">
                                    İptal
                                </button>

                                <button id="save_btn" class="btn btn-success">
                                    Kaydet
                                </button>
                            </div>

                        </div>

                    </div>
                `,
                didOpen: () => {

                    document.getElementById('source_text').value = sourceText;

                    const langSelect = document.getElementById('translation_lang');
                    const textInput = document.getElementById('translation_text');

                    function loadExisting() {
                        const lang = langSelect.value;
                        textInput.value = existingTranslations?.[lang] ?? '';
                    }

                    loadExisting();

                    langSelect.addEventListener('change', loadExisting);

                    document.getElementById('translate_btn').onclick = async () => {

                        const lang = langSelect.value;

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

                        textInput.value = data.translation ?? '';
                    };

                    document.getElementById('save_btn').onclick = () => {

                        const lang = langSelect.value;
                        const text = textInput.value;

                        saveTranslation(field, lang, text);

                    };

                    document.getElementById('cancel_btn').onclick = () => Swal.close();
                }
            });
        }

        function saveTranslation(field, lang, text) {

            fetch("{{ route('admin.saveCategoryDescriptionTranslation') }}", {

                    method: "POST",

                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },

                    body: JSON.stringify({
                        description_id: "{{ $desc?->id }}",
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

                        const msg = document.createElement("div");
                        msg.innerText = "Saved ✓";
                        msg.style.color = "green";
                        msg.style.marginTop = "10px";

                        document.querySelector('.swal2-html-container').appendChild(msg);

                        setTimeout(() => {
                            msg.remove();
                        }, 1500);
                    }

                });

        }
    </script>

@endsection
