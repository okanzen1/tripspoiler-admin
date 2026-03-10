@extends('layouts.admin')

@section('title', 'Sayfa Düzenle')

@section('content')
    <div class="container py-4">

        {{-- FLASH --}}
        @if (session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
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

        {{-- PAGE INFO --}}
        <div class="card mb-4">
            <div class="card-header fw-bold">Sayfa Bilgisi</div>
            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input class="form-control" value="{{ $page->slug }}" disabled>
                </div>

            </div>
        </div>

        {{-- CITY CONTENT --}}
        <div class="card">
            <div class="card-body">

                {{-- CITY SELECT --}}
                <div class="mb-3">
                    <label class="form-label">Şehir</label>
                    <select id="citySelect" class="form-select">
                        <option value="">Şehir seç</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>

        <div class="card">
            <div class="card-body">
                {{-- CONTENT FORM --}}
                <form id="pageContentForm" method="POST" action="{{ route('pages.contents.store', $page) }}"
                    class="d-none">
                    @csrf

                    <input type="hidden" name="city_id" id="city_id">
                    <input type="hidden" id="page_content_id">

                    <div class="mb-3">
                        <label class="form-label">Meta Başlık</label>
                        <div class="input-group">

                            <input type="text" name="meta_title" id="meta_title" class="form-control"
                                placeholder="Meta başlık (maksimum 60 karakter)" maxlength="60">

                            <button type="button" class="btn btn-outline-primary"
                                onclick="openTranslationModal('meta_title','meta_title',translations.meta_title)">
                                🌍
                            </button>

                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Açıklama</label>
                        <div class="input-group">

                            <input type="text" name="meta_description" id="meta_description" class="form-control"
                                maxlength="160" placeholder="Maksimum 160 karakter">

                            <button type="button" class="btn btn-outline-primary"
                                onclick="openTranslationModal('meta_description','meta_description',translations.meta_description)">
                                🌍
                            </button>

                        </div>
                    </div>

                    {{-- EDITOR --}}
                    <div class="mb-4">
                        <label class="form-label">İçerik</label>

                        <div id="editor" style="min-height: 400px;"></div>
                        <div class="mt-2 text-end">
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="openTranslationModal('content','contentInput',translations.content)">
                                🌍
                            </button>
                        </div>
                    </div>

                    <button type="button" id="saveBtn" class="btn btn-success"
                        style="position: fixed; bottom: 30px; right: 30px; z-index: 1050;">
                        Şehir İçeriğini Kaydet
                    </button>
                </form>

            </div>
        </div>

        {{-- PAGE IMAGES --}}
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Sayfa Görselleri</h5>
            </div>
            <div class="card-body">

                <form action="{{ route('images.upload') }}" class="dropzone mt-4" id="page-dropzone">
                    @csrf
                    <input type="hidden" name="source" value="{{ $page->slug }}_page">
                    <input type="hidden" name="source_id" value="{{ $page->id }}">
                </form>

                <div id="sortable-page-images" class="row mt-3">
                    @foreach ($page->images as $image)
                        <div class="col-md-3 mb-2" data-id="{{ $image->id }}">
                            <div class="border rounded p-1">
                                <img src="{{ $image->url }}" class="img-fluid">
                                <button type="button" class="btn btn-danger btn-sm w-100 mt-1 delete-page-image"
                                    data-delete-url="{{ route('images.destroy', $image) }}">
                                    Sil
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>

        @if ($page->slug === 'cities')
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Deneyim Kategorileri</h5>
                </div>

                <div class="card-body">

                    {{-- Yeni kategori --}}
                    <form id="categoryForm" class="row g-2 mb-3">
                        @csrf

                        <div class="col-md-5">
                            <select id="category_name" class="form-select" required>
                                <option value="">Kategori seç</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <input type="number" id="category_sort_order" class="form-control" placeholder="Sıra">
                        </div>

                        <div class="col-md-2 d-flex align-items-center">
                            <div class="form-check mt-2">
                                <input type="checkbox" id="category_status" class="form-check-input" checked>
                                <label class="form-check-label">Aktif</label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <button class="btn btn-success w-100">
                                + Yeni Kategori
                            </button>
                        </div>
                    </form>

                    {{-- Liste --}}
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Ad</th>
                                    <th>Sıra</th>
                                    <th>Status</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="categoryTableBody">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Şehir seçildiğinde kategoriler burada listelenecek.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        @endif

    </div>
@endsection
@section('scripts')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        const languages = ['en', ...@json($languages)];

        const translations = {
            meta_title: {},
            meta_description: {},
            content: {}
        };
    </script>

    <script>
        /* ---------------- CONFIG ---------------- */
        const LOCALE = "{{ app()->getLocale() }}";
        const DEFAULT_CITY_ID = {{ (int) $defaultCityId }};
        const PAGE_ID = {{ $page->id }};

        /* ---------------- ELEMENTS ---------------- */
        const citySelect = document.getElementById('citySelect');
        const form = document.getElementById('pageContentForm');
        const cityInput = document.getElementById('city_id');
        const pageContentIdInput = document.getElementById('page_content_id');
        const saveBtn = document.getElementById('saveBtn');
        const metaTitleInput = document.getElementById('meta_title');
        const metaDescriptionInput = document.getElementById('meta_description');

        let activeCityId = null;

        /* ---------------- QUILL ---------------- */
        const quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'İçeriği buraya yaz...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{
                        list: 'ordered'
                    }, {
                        list: 'bullet'
                    }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });

        /* ---------------- INIT ---------------- */
        document.addEventListener('DOMContentLoaded', () => {
            citySelect.value = DEFAULT_CITY_ID;
            loadCity(DEFAULT_CITY_ID);
        });

        citySelect.addEventListener('change', () => {
            loadCity(citySelect.value);
        });

        /* ---------------- LOAD CITY ---------------- */
        function loadCity(cityId) {
            if (!cityId) {
                hideForm();
                return;
            }

            activeCityId = String(cityId);
            cityInput.value = cityId;

            form.classList.remove('d-none');
            clearEditor();

            fetch(`/pages/${PAGE_ID}/contents/${cityId}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.ok ? res.json() : null)
                .then(data => {
                    if (!data || activeCityId !== String(cityId)) return;

                    pageContentIdInput.value = data.id ?? '';
                    setEditorContent(data.content);
                    setMetaFields(data);
                    translations.meta_title = data?.meta_title ?? {};
                    translations.meta_description = data?.meta_description ?? {};
                    translations.content = data?.content ?? {};

                    if (data?.id) {
                        loadCategories(data.id);
                    } else {
                        loadCategories(null);
                    }
                });
        }

        /* ---------------- SAVE ---------------- */
        saveBtn.addEventListener('click', () => {
            const html = quill.root.innerHTML.trim();
            document.getElementById('contentInput').value = html;
            form.submit();
        });

        /* ---------------- HELPERS ---------------- */
        function setEditorContent(value) {
            if (typeof value === 'object' && value !== null) {
                value = value[LOCALE] ?? '';
            }
            quill.root.innerHTML = value || '';
        }

        function clearEditor() {
            quill.root.innerHTML = '';
            metaTitleInput.value = '';
            metaDescriptionInput.value = '';

            translations.meta_title = {};
            translations.meta_description = {};
            translations.content = {};
        }

        function hideForm() {
            form.classList.add('d-none');
            clearEditor();
        }

        function setMetaFields(data) {
            if (!data) return;

            // meta_title
            if (typeof data.meta_title === 'object' && data.meta_title !== null) {
                metaTitleInput.value = data.meta_title[LOCALE] ?? '';
            } else {
                metaTitleInput.value = '';
            }

            // meta_description
            if (typeof data.meta_description === 'object' && data.meta_description !== null) {
                metaDescriptionInput.value = data.meta_description[LOCALE] ?? '';
            } else {
                metaDescriptionInput.value = '';
            }
        }
    </script>
    <script>
        Dropzone.autoDiscover = false;

        new Dropzone("#page-dropzone", {
            maxFilesize: 2,
            acceptedFiles: 'image/*',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            success: function() {
                location.reload();
            },
            error: function(file, msg) {
                console.error(msg);
                alert('Upload hatası');
            }
        });

        // Sortable
        const pageGrid = document.getElementById('sortable-page-images');

        if (pageGrid) {
            new Sortable(pageGrid, {
                animation: 150,
                onEnd: function() {
                    const order = [];
                    pageGrid.querySelectorAll('[data-id]').forEach(el => order.push(el.dataset.id));

                    fetch("{{ route('images.sort') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Accept": "application/json",
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            order
                        })
                    });
                }
            });
        }

        // Delete
        document.querySelectorAll('.delete-page-image').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Silinsin mi?')) return;

                fetch(this.dataset.deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => {
                        if (!res.ok) throw new Error();
                        location.reload();
                    })
                    .catch(() => alert('Silme hatası'));
            });
        });
    </script>

    @if ($page->slug === 'cities')
        <script>
            const FIXED_CITY_CATEGORIES = [
                "City Overview",
                "History & Identity",
                "Iconic Landmarks",
                "Neighborhood Guide",
                "Scenic Views",
                "Food & Local Culture",
                "Travel Tips",
                "Why Visit"
            ];

            let activePageContentIdForCategories = null;

            const categoryForm = document.getElementById('categoryForm');
            const categoryTableBody = document.getElementById('categoryTableBody');
            const categoryNameInput = document.getElementById('category_name');
            const categorySortInput = document.getElementById('category_sort_order');
            const categoryStatusInput = document.getElementById('category_status');

            function renderCategoryName(name) {
                if (typeof name === 'object' && name !== null) {
                    return name[LOCALE] ?? '';
                }
                return name ?? '';
            }

            function renderCategories(items) {

                categoryTableBody.innerHTML = '';

                if (!items || items.length === 0) {
                    categoryTableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Henüz kategori eklenmedi.
                            </td>
                        </tr>
                    `;
                    return;
                }

                items.forEach(cat => {
                    categoryTableBody.innerHTML += `
                        <tr>
                            <td>${cat.id}</td>
                            <td>${renderCategoryName(cat.name)}</td>
                            <td>${cat.sort_order ?? 0}</td>
                            <td>
                                <span class="badge ${cat.status ? 'bg-success' : 'bg-secondary'} toggleStatus"
                                    style="cursor:pointer"
                                    data-id="${cat.id}">
                                    ${cat.status ? 'Aktif' : 'Pasif'}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="/experience-categories/${cat.id}/edit"
                                class="btn btn-sm btn-outline-primary me-1">
                                    Düzenle
                                </a>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger deleteCategory"
                                        data-id="${cat.id}">
                                    Sil
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }

            function loadCategories(pageContentId) {

                if (!pageContentId) {
                    activePageContentIdForCategories = null;
                    renderCategories([]);
                    return;
                }

                activePageContentIdForCategories = pageContentId;

                fetch(`/page-contents/${pageContentId}/experience-categories`)
                    .then(res => res.json())
                    .then(data => renderCategories(data));
            }

            categoryForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const selectedCategory = categoryNameInput.value;

                if (!selectedCategory) {
                    alert("Kategori seç.");
                    return;
                }

                const existingNames = Array.from(categoryTableBody.querySelectorAll('tr td:nth-child(2)'))
                    .map(td => td.innerText.trim());

                if (existingNames.includes(selectedCategory)) {
                    alert("Bu kategori zaten eklendi.");
                    return;
                }

                if (!activePageContentIdForCategories) {
                    alert('Önce şehir seç.');
                    return;
                }

                fetch(`/page-contents/${activePageContentIdForCategories}/experience-categories`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            name: categoryNameInput.value,
                            sort_order: categorySortInput.value ?
                                parseInt(categorySortInput.value) : 0,
                            status: categoryStatusInput.checked
                        })
                    })
                    .then(res => res.json())
                    .then(() => {
                        categoryNameInput.value = '';
                        categorySortInput.value = '';
                        categoryStatusInput.checked = true;

                        loadCategories(activePageContentIdForCategories);
                    });
            });

            document.addEventListener('click', function(e) {

                if (!e.target.classList.contains('deleteCategory')) return;

                if (!confirm('Silinsin mi?')) return;

                fetch(`/experience-categories/${e.target.dataset.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(() => {
                        loadCategories(activePageContentIdForCategories);
                    });
            });

            document.addEventListener('click', function(e) {

                if (!e.target.classList.contains('toggleStatus')) return;

                fetch(`/experience-categories/${e.target.dataset.id}/toggle-status`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(() => {
                        loadCategories(activePageContentIdForCategories);
                    });

            });

            function populateCategorySelect() {
                const select = document.getElementById('category_name');
                select.innerHTML = '<option value="">Kategori seç</option>';

                FIXED_CITY_CATEGORIES.forEach(cat => {
                    select.innerHTML += `<option value="${cat}">${cat}</option>`;
                });
            }

            if (categoryNameInput) {
                populateCategorySelect();
            }
        </script>
        <script>
            function openTranslationModal(field, sourceInputId, existingTranslations) {

                if (!pageContentIdInput.value) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Önce kaydet',
                        text: 'Bu şehir için önce içerik kaydet.'
                    });
                    return;
                }

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
                    sourceText = document.getElementById(sourceInputId).value ?? '';
                }

                Swal.fire({
                    title: "Çeviri " + field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                    width: 700,
                    showConfirmButton: false,
                    html: `
                        <div>

                            <label>Kaynak Dil</label>
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

                fetch("{{ route('admin.savePageContentTranslation') }}", {

                        method: "POST",

                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },

                        body: JSON.stringify({
                            page_content_id: pageContentIdInput.value,
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
    @endif
@endsection
