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
                        <label>Kategori Adı</label>
                        <input type="text" name="name" class="form-control"
                            value="{{ $category->getTranslation('name', app()->getLocale()) }}">
                    </div>

                    <div class="mb-3">
                        <label>Sıra</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ $category->sort_order }}">
                    </div>

                    {{-- DESCRIPTION --}}
                    <div class="card mt-4">
                        <div class="card-header">
                            <strong>Description</strong>
                        </div>
                        <div class="card-body">

                            @php
                                $desc = $category->descriptions()->first();
                                $content = $desc?->getTranslation('description', app()->getLocale()) ?? '';
                            @endphp

                            <div id="editor" style="min-height: 350px;"></div>

                            <input type="hidden" name="description" id="descriptionInput">
                        </div>
                    </div>

                    <button type="button" id="saveBtn" class="btn btn-primary mt-4">
                        Güncelle
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

            // 🔥 HTML’i düzgün inject et
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

@endsection
