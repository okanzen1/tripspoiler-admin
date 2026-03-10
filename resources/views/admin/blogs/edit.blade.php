@extends('layouts.admin')

@section('title', 'Blog Düzenle')

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

            <form method="POST" action="{{ route('blogs.update', $blog) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Blog Adı</label>
                    <div class="input-group">

                        <input id="title_en" type="text" name="title" class="form-control"
                            value="{{ old('title', $blog->getTranslation('title', 'en')) }}" required>

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('title','title_en',translations.title)">
                            🌍
                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Özet</label>
                    <div class="input-group">

                        <textarea id="excerpt_en" name="excerpt" class="form-control" rows="3">{{ old('excerpt', $blog->getTranslation('excerpt', 'en')) }}</textarea>

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('excerpt','excerpt_en',translations.excerpt)">
                            🌍
                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <div class="input-group">

                        <input id="slug_en" type="text" name="slug" class="form-control"
                            value="{{ old('slug', $blog->getTranslation('slug', 'en')) }}" required>

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('slug','slug_en',translations.slug)">
                            🌍
                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Meta Title</label>
                    <div class="input-group">

                        <input id="meta_title_en" type="text" name="meta_title" class="form-control"
                            value="{{ old('meta_title', $blog->getTranslation('meta_title', 'en')) }}">

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('meta_title','meta_title_en',translations.meta_title)">
                            🌍
                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Meta Description</label>
                    <div class="input-group">

                        <input id="meta_description_en" type="text" name="meta_description" class="form-control"
                            value="{{ old('meta_description', $blog->getTranslation('meta_description', 'en')) }}">

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('meta_description','meta_description_en',translations.meta_description)">
                            🌍
                        </button>

                    </div>
                </div>

                <div class="mb-3">
                    <label>Şehir</label>
                    <input type="text" class="form-control" value="{{ $blog->city->name ?? '-' }}" readonly>
                </div>

                @php($locale = app()->getLocale())

                <div class="mb-3">
                    <label>Tema / Etiketler</label>
                    <div class="input-group">

                        <input id="themes_en" type="text" name="themes" class="form-control"
                            placeholder="Art, History, Culture"
                            value="{{ old(
                                'themes',
                                $blog->getTranslation('themes', 'en') ? implode(', ', $blog->getTranslation('themes', 'en')) : '',
                            ) }}">

                        <button type="button" class="btn btn-outline-primary"
                            onclick="openTranslationModal('themes','themes_en',translations.themes)">
                            🌍
                        </button>

                    </div>
                    <small class="text-muted">
                        Virgülle ayırarak girin (örn: Art, History, Culture)
                    </small>
                </div>

                <div class="mb-3">
                    <label>Bağlı Aktiviteler</label>

                    <select name="activities[]" id="activitySelect" class="form-select" multiple>
                        @foreach ($activities as $activity)
                            <option value="{{ $activity->id }}" @selected($blog->activities->contains($activity->id))>

                                {{ $activity->id }} -
                                {{ $activity->getTranslation('name', app()->getLocale()) }}

                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="status" class="form-select">
                        <option value="1" @selected($blog->status)>Aktif</option>
                        <option value="0" @selected(!$blog->status)>Pasif</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Sıralama</label>
                    <input type="number" name="sort_order" class="form-control"
                        value="{{ old('sort_order', $blog->sort_order) }}">
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
            <h5 class="mb-0">Blog Görselleri</h5>
        </div>
        <div class="card-body">

            <form action="{{ route('images.upload') }}" class="dropzone mt-4" id="blog-dropzone">
                @csrf
                <input type="hidden" name="source" value="blog">
                <input type="hidden" name="source_id" value="{{ $blog->id }}">
            </form>

            <div id="sortable-images" class="row mt-3">
                @foreach ($blog->images as $image)
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
            <h5 class="mb-0">İçerikler</h5>

            <a href="{{ route('blogs.content.create', $blog) }}" class="btn btn-success btn-sm">
                + Yeni İçerik
            </a>

        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Kısa Özet</th>
                            <th>Status</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($contents as $content)
                            <tr>
                                <td>{{ $content->id }}</td>

                                <td>
                                    {{ Str::limit(strip_tags($content->getTranslation('content', app()->getLocale())), 120) }}
                                </td>

                                <td>
                                    @if ($content->status)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Pasif</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('blogs.content.edit', [$blog, $content]) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Düzenle
                                    </a>

                                    <form action="{{ route('blogs.content.destroy', [$blog, $content]) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-outline-danger">
                                            Sil
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    Henüz içerik eklenmedi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    <div style="height: 120px;"></div>

@endsection

@section('scripts')
    <script>
        const languages = ['en', ...@json($languages)];

        const translations = {
            title: @json($blog->getTranslations('title')),
            excerpt: @json($blog->getTranslations('excerpt')),
            themes: @json($blog->getTranslations('themes')),
            slug: @json($blog->getTranslations('slug')),
            meta_title: @json($blog->getTranslations('meta_title')),
            meta_description: @json($blog->getTranslations('meta_description'))
        };
    </script>

    <script>
        // Dropzone
        Dropzone.autoDiscover = false;

        new Dropzone("#blog-dropzone", {
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
        $(document).ready(function() {
            $('#activitySelect').select2({
                placeholder: "Aktivite ara ve seç...",
                width: '100%'
            });
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

        function openTranslationModal(field, sourceInputId, existingTranslations) {

            let targetOptions = '';

            languages.forEach(lang => {
                if (lang !== 'en') {
                    targetOptions += `<option value="${lang}">${lang.toUpperCase()}</option>`;
                }
            });

            let sourceText = document.getElementById(sourceInputId).value ?? '';

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

                        let textForTranslation = sourceText;

                        if (field === 'slug') {
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

                        if (field === 'slug') {
                            result = slugify(result);
                        }

                        textInput.value = result;

                        translateBtn.disabled = false;

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

            fetch("{{ route('admin.saveBlogTranslation') }}", {

                    method: "POST",

                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },

                    body: JSON.stringify({

                        blog_id: {{ $blog->id }},
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
