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
                    <input type="text" name="title" class="form-control" value="{{ old('title', $blog->title) }}"
                        required>
                </div>

                <div class="mb-3">
                    <label>Özet</label>
                    <textarea name="excerpt" class="form-control" rows="3">{{ old('excerpt', $blog->excerpt) }}</textarea>
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <input type="text" name="slug" class="form-control"
                        value="{{ old('slug', $blog->getTranslation('slug', 'tr')) }}" required>
                </div>

                <div class="mb-3">
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" class="form-control"
                        value="{{ old('meta_title', $blog->getTranslation('meta_title', 'tr')) }}">
                </div>

                <div class="mb-3">
                    <label>Meta Description</label>
                    <input type="text" name="meta_description" class="form-control"
                        value="{{ old('meta_description', $blog->getTranslation('meta_description', 'tr')) }}">
                </div>

                <div class="mb-3">
                    <label>Şehir</label>
                    <select name="city_id" class="form-select" required>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected(old('city_id', $blog->city_id) == $city->id)>
                                {{ $city->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @php($locale = app()->getLocale())

                <div class="mb-3">
                    <label>Tema / Etiketler</label>
                    <input type="text" name="themes" class="form-control" placeholder="Art, History, Culture"
                        value="{{ old(
                            'themes',
                            $blog->getTranslation('themes', $locale) ? implode(', ', $blog->getTranslation('themes', $locale)) : '',
                        ) }}">
                    <small class="text-muted">
                        Virgülle ayırarak girin (örn: Art, History, Culture)
                    </small>
                </div>

                <div class="mb-3">
                    <label>Kaynak (Opsiyonel)</label>
                    <input name="source" value="{{ old('source', $blog->source) }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Kaynak ID (Opsiyonel)</label>
                    <input name="source_id" value="{{ old('source_id', $blog->source_id) }}" class="form-control">
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
                        @forelse($blog->content ? [$blog->content] : [] as $content)
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
@endsection
