@extends('layouts.admin')

@section('title', 'Sayfa Düzenle')

@section('content')

<div class="container py-4">

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

    {{-- PAGE --}}
    <div class="card mb-4">
        <div class="card-header fw-bold">Sayfa Bilgisi</div>
        <div class="card-body">
            <form method="POST" action="{{ route('pages.update', $page) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input name="slug"
                           class="form-control"
                           value="{{ old('slug', $page->slug) }}"
                           required>
                </div>

                <button class="btn btn-primary">Slug Güncelle</button>
            </form>
        </div>
    </div>

    {{-- CITY CONTENT --}}
    <div class="card">
        <div class="card-header fw-bold">Şehir Bazlı İçerik</div>
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

            {{-- FORM --}}
            <form id="pageContentForm"
                  method="POST"
                  action="{{ route('pages.contents.store', $page) }}"
                  class="d-none">
                @csrf

                <input type="hidden" name="city_id" id="city_id">
                <input type="hidden" id="page_content_id">

                <div class="mb-3">
                    <label class="form-label">Meta Title</label>
                    <input name="meta_title" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">H1</label>
                    <input name="h1" class="form-control">
                </div>

                {{-- QUILL --}}
                <div class="mb-4">
                    <div class="card-header"><strong>İçerik</strong></div>
                    <div class="card-body">
                        <div id="editor" style="min-height: 400px;"></div>
                        <input type="hidden" name="content" id="contentInput">
                    </div>
                </div>

                <button type="button"
                        id="saveBtn"
                        class="btn btn-success"
                        style="position: fixed; bottom: 30px; right: 30px; z-index: 1050;">
                    Şehir İçeriğini Kaydet
                </button>

            </form>

        </div>
    </div>

</div>
@endsection

@section('scripts')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script>
const CURRENT_LOCALE = "{{ app()->getLocale() }}";
const DEFAULT_CITY_ID = {{ (int) $defaultCityId }};
const PAGE_ID = {{ $page->id }};

const citySelect = document.getElementById('citySelect');
const form = document.getElementById('pageContentForm');
const cityInput = document.getElementById('city_id');
const pageContentIdInput = document.getElementById('page_content_id');

let activeCityId = null;

// ---------------- QUILL ----------------
const quill = new Quill('#editor', {
    theme: 'snow',
    placeholder: 'İçeriği buraya yaz...',
    modules: {
        toolbar: {
            container: [
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'image'],
                ['clean']
            ],
            handlers: {
                image: imageHandler
            }
        }
    }
});

// ---------------- INIT ----------------
document.addEventListener('DOMContentLoaded', () => {
    citySelect.value = DEFAULT_CITY_ID;
    loadCity(DEFAULT_CITY_ID);
});

citySelect.addEventListener('change', () => {
    loadCity(citySelect.value);
});

// ---------------- LOAD CITY ----------------
function loadCity(cityId) {
    if (!cityId) {
        hideForm();
        return;
    }

    activeCityId = String(cityId);

    cityInput.value = cityId;
    form.classList.remove('d-none');
    clearForm();

    fetch(`/pages/${PAGE_ID}/contents/${cityId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.ok ? r.json() : null)
    .then(data => {
        if (activeCityId !== String(cityId)) return;
        if (!data) return;

        pageContentIdInput.value = data.id;

        setVal('meta_title', data.meta_title);
        setVal('meta_description', data.meta_description);
        setVal('h1', data.h1);
        setEditor(data.content);
    });
}

// ---------------- SAVE ----------------
document.getElementById('saveBtn').addEventListener('click', () => {
    const html = quill.root.innerHTML.trim();

    if (html === '' || html === '<p><br></p>') {
        alert('İçerik boş olamaz');
        return;
    }

    document.getElementById('contentInput').value = html;
    form.submit();
});

// ---------------- HELPERS ----------------
function setVal(name, value) {
    if (typeof value === 'object' && value !== null) {
        value = value[CURRENT_LOCALE] ?? '';
    }
    const field = form.querySelector(`[name="${name}"]`);
    if (field) field.value = value ?? '';
}

function setEditor(value) {
    if (typeof value === 'object' && value !== null) {
        value = value[CURRENT_LOCALE] ?? '';
    }
    quill.root.innerHTML = value ?? '';
}

function clearForm() {
    form.querySelectorAll('input:not([type=hidden]), textarea')
        .forEach(el => el.value = '');
    quill.root.innerHTML = '';
}

function hideForm() {
    form.classList.add('d-none');
    clearForm();
}

// ---------------- IMAGE UPLOAD ----------------
function imageHandler() {
    if (!pageContentIdInput.value) {
        alert('Önce içeriği kaydetmelisin');
        return;
    }

    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.click();

    input.onchange = async () => {
        const file = input.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('source', 'page_content');
        formData.append('source_id', pageContentIdInput.value);

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

        } catch {
            alert('Görsel yüklenirken hata oluştu');
        }
    };
}
</script>
@endsection
