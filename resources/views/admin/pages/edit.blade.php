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
                        <input type="text" name="meta_title" id="meta_title" class="form-control" placeholder="Meta başlık (maksimum 60 karakter)" maxlength="60">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Açıklama</label>
                        <input type="text" name="meta_description" id="meta_description" class="form-control" maxlength="160" placeholder="Maksimum 160 karakter">
                    </div>

                    {{-- EDITOR --}}
                    <div class="mb-4">
                        <label class="form-label">İçerik</label>

                        <div id="editor" style="min-height: 400px;"></div>
                        <input type="hidden" name="content" id="contentInput">
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

                <form action="{{ route('images.upload') }}"
                    class="dropzone mt-4"
                    id="page-dropzone">
                    @csrf
                    <input type="hidden" name="source" value="{{ $page->slug }}_page">
                    <input type="hidden" name="source_id" value="{{ $page->id }}">
                </form>

                <div id="sortable-page-images" class="row mt-3">
                    @foreach ($page->images as $image)
                        <div class="col-md-3 mb-2" data-id="{{ $image->id }}">
                            <div class="border rounded p-1">
                                <img src="{{ $image->url }}" class="img-fluid">
                                <button type="button"
                                        class="btn btn-danger btn-sm w-100 mt-1 delete-page-image"
                                        data-delete-url="{{ route('images.destroy', $image) }}">
                                    Sil
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>

    </div>
@endsection
@section('scripts')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

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
                        body: JSON.stringify({ order })
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
@endsection
