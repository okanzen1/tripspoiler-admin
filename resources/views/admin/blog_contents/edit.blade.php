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
                    <input type="text" name="title" class="form-control"
                        value="{{ old('title', $content->title) }}" required>
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

        {{-- KISA ÖZET --}}
        <div class="card mb-4">
            <div class="card-header"><strong>Kısa Özet (Opsiyonel)</strong></div>
            <div class="card-body">
                <textarea name="excerpt" class="form-control" rows="4">{{ old('excerpt', $content->excerpt) }}</textarea>
            </div>
        </div>

        {{-- ANA İÇERİK --}}
        <div class="card mb-5">
            <div class="card-header"><strong>Ana İçerik</strong></div>
            <div class="card-body">

                <div id="editor" style="min-height: 400px;">
                    {!! old('content', $content->content) !!}
                </div>

                <input type="hidden" name="content" id="contentInput">
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
@endsection
