@extends('layouts.admin')

@section('title', 'Aktivite Düzenle')

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

            <form id="activityForm" method="POST" action="{{ route('activities.update', $activity) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Aktivite Adı</label>
                    <div class="input-group">

                        <input id="name_en" name="name"
                            value="{{ old('name', $activity->getTranslation('name', 'en')) }}" class="form-control">

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('name','name_en',translations.name)">

                            🌍

                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <div class="input-group">

                        <input id="slug_en" name="slug"
                            value="{{ old('slug', $activity->getTranslation('slug', 'en')) }}" class="form-control"
                            required>

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('slug','slug_en',translations.slug)">

                            🌍

                        </button>

                    </div>
                    <small class="text-muted">
                        URL slug (küçük harf, boşluksuz).
                    </small>
                </div>

                <div class="mb-3">
                    <label>Meta Başlık</label>
                    <div class="input-group">

                        <input id="meta_title_en" name="meta_title"
                            value="{{ old('meta_title', $activity->getTranslation('meta_title', 'en')) }}"
                            class="form-control">

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('meta_title','meta_title_en',translations.meta_title)">

                            🌍

                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Meta Açıklama</label>

                    <div class="input-group">

                        <textarea id="meta_description_en" name="meta_description" class="form-control">{{ old('meta_description', $activity->getTranslation('meta_description', 'en')) }}</textarea>

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('meta_description','meta_description_en',translations.meta_description)">

                            🌍

                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Şehir</label>
                    <select name="city_id" class="form-select" required>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected(old('city_id', $activity->city_id) == $city->id)>
                                {{ $city->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>İş Ortakları</label>
                    <select name="affiliate_id" class="form-select">
                        <option value="">- yok -</option>
                        @foreach ($affiliatePartners as $partner)
                            <option value="{{ $partner->id }}" @selected(old('affiliate_id', $activity->affiliate_id) == $partner->id)>
                                {{ $partner->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>İş Ortağı Ürün ID <span class="text-danger">*</span></label>
                    <input type="number" name="source_product_id"
                        value="{{ old('source_product_id', $activity->source_product_id) }}" class="form-control" required>
                    <small class="text-muted">
                        Aynı Affiliate için aynı Product ID ikinci kez eklenemez.
                    </small>
                </div>

                <div class="mb-3">
                    <label>İş Ortağı Linki</label>
                    <input type="url" name="affiliate_link"
                        value="{{ old('affiliate_link', $activity->affiliate_link) }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Most Popular</label>
                    <select name="most_popular" class="form-select">
                        <option value="1" @selected(old('most_popular', $activity->most_popular))>
                            Evet
                        </option>
                        <option value="0" @selected(!old('most_popular', $activity->most_popular))>
                            Hayır
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="status" class="form-select">
                        <option value="1" @selected(old('status', $activity->status))>Aktif</option>
                        <option value="0" @selected(!old('status', $activity->status))>Pasif</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Sıralama</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $activity->sort_order) }}"
                        class="form-control">
                </div>

                <div class="mb-3">
                    <label>Aktivite Tipi</label>
                    <select name="activity_type" class="form-select" required>
                        @foreach ($productTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('activity_type', $activity->activity_type) === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Duration</label>


                    <div class="input-group">

                        <input id="duration_en" type="text" name="duration" class="form-control"
                            placeholder="45 minutes / 1–1.5 hours / Full day"
                            value="{{ old('duration', $activity->getTranslation('duration', 'en')) }}">

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('duration','duration_en',translations.duration)">

                            🌍

                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Audio Guide</label>
                    <select name="audio_guide" class="form-select">
                        <option value="1" @selected(old('audio_guide', $activity->audio_guide))>
                            Included
                        </option>
                        <option value="0" @selected(!old('audio_guide', $activity->audio_guide))>
                            Not included
                        </option>
                    </select>
                </div>

                <div class="mb-3">

                    <label class="mb-2 d-block">
                        <strong>Açıklama</strong>
                    </label>

                    <div id="activity-editor" style="min-height:300px">
                        {!! old('description', $activity->getTranslation('description', 'en')) !!}
                    </div>

                    <input type="hidden" name="description" id="descriptionInput">

                    <div class="mt-2 text-end">

                        <button type="button" class="btn btn-outline-primary btn-sm"
                            onclick="openTranslationModal('description','descriptionInput',translations.description)">

                            🌍 Translate Description

                        </button>

                    </div>

                </div>

                <button type="submit" class="btn btn-primary"
                    style="position: fixed; bottom: 60px; right: 20px; z-index: 1050;">
                    Güncelle
                </button>

            </form>

        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Aktivite Görselleri</h5>
        </div>
        <div class="card-body">

            <form action="{{ route('images.upload') }}" class="dropzone mt-4" id="activity-dropzone">
                @csrf
                <input type="hidden" name="source" value="activity">
                <input type="hidden" name="source_id" value="{{ $activity->id }}">
            </form>

            <div id="sortable-images" class="row mt-3">
                @foreach ($activity->images as $image)
                    <div class="col-md-3 mb-2" data-id="{{ $image->id }}">
                        <div class="border rounded p-1">
                            <img src="{{ $image->url }}" class="img-fluid">
                            <button type="button" class="btn btn-danger btn-sm w-100 mt-1 delete-image"
                                data-delete-url="{{ route('images.destroy', $image) }}">
                                Sil
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Activity FAQ</h5>

            <a href="{{ route('faqs.create', [
                'source' => 'activity-show',
                'source_id' => $activity->id,
                'return_to' => url()->current(),
            ]) }}"
                class="btn btn-primary btn-sm">
                + FAQ Ekle
            </a>
        </div>

        <div class="card-body">

            @if ($activity->activityShowFaqs->isEmpty())
                <p class="text-muted">Henüz FAQ yok</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Soru</th>
                            <th>Sıra</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($activity->activityShowFaqs as $faq)
                            <tr>
                                <td>{{ $faq->id }}</td>

                                <td>
                                    {{ \Str::limit($faq->getTranslation('question', 'en'), 50) }}
                                </td>

                                <td>{{ $faq->sort_order }}</td>

                                <td>
                                    @if ($faq->status)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Pasif</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('faqs.edit', ['faq' => $faq->id, 'return_to' => url()->current()]) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Düzenle
                                    </a>

                                    <form action="{{ route('faqs.destroy', $faq) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="return_to" value="{{ url()->current() }}">
                                        <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Silinsin mi?')">
                                            Sil
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            @endif

        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Activity Reviews</h5>

            <a href="{{ route('reviews.create', [
                'source' => 'activity-show',
                'source_id' => $activity->id,
                'return_to' => url()->current(),
            ]) }}"
                class="btn btn-success btn-sm">
                + Review Ekle
            </a>
        </div>

        <div class="card-body">

            @if ($activity->activityShowReviews->isEmpty())
                <p class="text-muted">Henüz review yok</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>İsim</th>
                            <th>Puan</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($activity->activityShowReviews as $review)
                            <tr>
                                <td>{{ $review->id }}</td>
                                <td>{{ $review->name }}</td>
                                <td>{{ $review->rating }}</td>

                                <td>
                                    @if ($review->approved)
                                        <span class="badge bg-success">Yayında</span>
                                    @else
                                        <span class="badge bg-secondary">Bekliyor</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('reviews.edit', [
                                        'review' => $review->id,
                                        'return_to' => url()->current(),
                                    ]) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Düzenle
                                    </a>

                                    <form action="{{ route('reviews.destroy', $review) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')

                                        <input type="hidden" name="return_to" value="{{ url()->current() }}">

                                        <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Silinsin mi?')">
                                            Sil
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            @endif

        </div>
    </div>

@endsection
@section('scripts')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        const languages = ['en', ...@json($languages)];
        const translations = {
            name: @json($activity->getTranslations('name')),
            slug: @json($activity->getTranslations('slug')),
            meta_title: @json($activity->getTranslations('meta_title')),
            meta_description: @json($activity->getTranslations('meta_description')),
            duration: @json($activity->getTranslations('duration')),
            description: @json($activity->getTranslations('description'))
        };
    </script>

    <script>
        // Dropzone
        Dropzone.autoDiscover = false;

        new Dropzone("#activity-dropzone", {
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
        const grid = document.getElementById('sortable-images');

        new Sortable(grid, {
            animation: 150,
            onEnd: function() {
                const order = [];
                grid.querySelectorAll('[data-id]').forEach(el => order.push(el.dataset.id));

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

        // Delete
        document.querySelectorAll('.delete-image').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Silinsin mi?')) return;

                fetch(this.dataset.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                }).then(res => {
                    if (!res.ok) throw new Error('Silme başarısız');
                    location.reload();
                }).catch(() => alert('Silme hatası'));
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const quill = new Quill('#activity-editor', {
                theme: 'snow',
                placeholder: 'Aktivite açıklamasını yaz...',
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

            function isQuillEmpty(quill) {
                return quill.getText().trim().length === 0;
            }

            document.getElementById('activityForm').addEventListener('submit', function(e) {

                const html = quill.root.innerHTML.trim();
                const text = quill.getText().trim();

                // BOŞSA NULL GÖNDER
                if (text.length === 0) {
                    document.getElementById('descriptionInput').value = '';
                    return;
                }

                // DOLUYSA NORMAL HTML
                document.getElementById('descriptionInput').value = html;
            });

            function imageHandler() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.click();

                input.onchange = async () => {
                    const file = input.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('source', 'activity_description');
                    formData.append('source_id', '{{ $activity->id }}');

                    const res = await fetch('{{ route('images.upload') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await res.json();
                    if (!data.url) return alert('Upload failed');

                    const range = quill.getSelection(true);
                    quill.insertEmbed(range.index, 'image', data.url);
                    quill.setSelection(range.index + 1);
                };
            }
        });
    </script>

    <script>
        function slugify(text) {

            const map = {
                'ç': 'c',
                'ğ': 'g',
                'ı': 'i',
                'ö': 'o',
                'ş': 's',
                'ü': 'u',
                'Ç': 'c',
                'Ğ': 'g',
                'İ': 'i',
                'Ö': 'o',
                'Ş': 's',
                'Ü': 'u'
            };

            return text
                .replace(/[çğıöşüÇĞİÖŞÜ]/g, m => map[m])
                .toLowerCase()

                // unicode karakterleri korur (ar, zh, ja, ko, ru vs)
                .replace(/[^\p{L}\p{N}\s-]/gu, '')

                .replace(/\s+/g, '-')
                .replace(/--+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function saveTranslation(field, lang, text) {

            fetch("{{ route('admin.saveTranslation') }}", {

                    method: "POST",

                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },

                    body: JSON.stringify({
                        activity_id: {{ $activity->id }},
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

                        renderTranslationStatus(field);

                        const msg = document.createElement("div");
                        msg.innerText = "Saved ✓";
                        msg.style.color = "green";
                        msg.style.marginTop = "10px";

                        document.querySelector('.swal2-html-container').appendChild(msg);

                        setTimeout(() => msg.remove(), 1500);

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

            let sourceText = '';

            if (sourceInputId === 'descriptionInput') {

                sourceText = document.querySelector('#activity-editor .ql-editor').innerHTML;

            } else {

                const el = document.getElementById(sourceInputId);
                sourceText = el ? el.value : '';

            }

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

                        <div id="translation_status" class="mt-2 small text-muted"></div>

                        <div class="mt-3 d-flex justify-content-between">

                            <div>

                                <button id="translate_btn" class="btn btn-primary">
                                    Çevir
                                </button>

                                <button id="translate_all_btn" class="btn btn-dark ms-2">
                                    Tümünü Çevir
                                </button>

                            </div>

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
                    const translateBtn = document.getElementById('translate_btn');

                    function loadExisting() {

                        const lang = langSelect.value;

                        textInput.value = translations[field]?.[lang] ??
                            existingTranslations?.[lang] ??
                            '';

                    }

                    loadExisting();

                    langSelect.addEventListener('change', loadExisting);

                    renderTranslationStatus(field);

                    translateBtn.onclick = async () => {

                        const lang = langSelect.value;

                        translateBtn.disabled = true;
                        translateBtn.innerText = "Çeviriliyor...";

                        let textForTranslation = sourceText;

                        if (field === "slug") {
                            textForTranslation = sourceText.replaceAll('-', ' ');
                        }

                        const res = await fetch("/admin/translate", {

                            method: "POST",

                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },

                            body: JSON.stringify({
                                text: textForTranslation,
                                lang: lang
                            })

                        });

                        const data = await res.json();

                        let result = data.translation ?? '';

                        if (field === "slug") {
                            result = slugify(result);
                        }

                        textInput.value = result;

                        translateBtn.disabled = false;
                        translateBtn.innerText = "Çevir";

                    };

                    document.getElementById('save_btn').onclick = () => {

                        const lang = langSelect.value;
                        const text = textInput.value;

                        saveTranslation(field, lang, text);

                    };

                    document.getElementById('cancel_btn').onclick = () => Swal.close();


                    document.getElementById('translate_all_btn').onclick = async () => {

                        const btn = document.getElementById('translate_all_btn');

                        btn.disabled = true;
                        btn.innerText = "Çevriliyor...";

                        Swal.showLoading();

                        await Promise.all(

                            languages
                            .filter(lang => lang !== 'en')
                            .map(async (lang) => {

                                let textForTranslation = sourceText;

                                if (field === "slug") {
                                    textForTranslation = sourceText.replaceAll('-', ' ');
                                }

                                const res = await fetch("/admin/translate", {

                                    method: "POST",

                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },

                                    body: JSON.stringify({
                                        text: textForTranslation,
                                        lang: lang
                                    })

                                });

                                const data = await res.json();

                                let result = data.translation ?? '';

                                if (field === "slug") {
                                    result = slugify(result);
                                }

                                saveTranslation(field, lang, result);

                                if (!translations[field]) {
                                    translations[field] = {};
                                }

                                translations[field][lang] = result;

                            })

                        );

                        Swal.hideLoading();

                        renderTranslationStatus(field);

                        const msg = document.createElement("div");
                        msg.innerText = "All translations saved ✓";
                        msg.style.color = "green";
                        msg.style.marginTop = "10px";

                        document.querySelector('.swal2-html-container').appendChild(msg);

                        setTimeout(() => {
                            msg.remove();
                        }, 2000);

                        const currentLang = langSelect.value;

                        textInput.value = translations[field]?.[currentLang] ?? '';

                        btn.disabled = false;
                        btn.innerText = "Tümünü Çevir";

                    };

                }

            });

        }


        function renderTranslationStatus(field) {

            const container = document.getElementById('translation_status');

            let html = '<strong>Languages:</strong> ';

            languages.forEach(lang => {

                if (lang === 'en') return;

                const exists = translations[field]?.[lang];

                if (Array.isArray(exists)) {
                    if (exists.length > 0) {
                        html += `<span style="color:green;margin-right:8px;">${lang.toUpperCase()} ✓</span>`;
                    } else {
                        html += `<span style="color:#999;margin-right:8px;">${lang.toUpperCase()} -</span>`;
                    }
                } else if (exists && exists.trim() !== '') {
                    html += `<span style="color:green;margin-right:8px;">${lang.toUpperCase()} ✓</span>`;
                } else {
                    html += `<span style="color:#999;margin-right:8px;">${lang.toUpperCase()} -</span>`;
                }

            });

            container.innerHTML = html;

        }
    </script>
@endsection
