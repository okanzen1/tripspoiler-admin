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
                    <input name="name" value="{{ old('name', $activity->name) }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <input name="slug" value="{{ old('slug', $activity->slug) }}" class="form-control" required>
                    <small class="text-muted">
                        URL slug (küçük harf, boşluksuz).
                    </small>
                </div>

                <div class="mb-3">
                    <label>Meta Başlık</label>
                    <input name="meta_title" value="{{ old('meta_title', $activity->meta_title) }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Meta Açıklama</label>
                    <textarea name="meta_description" class="form-control">{{ old('meta_description', $activity->meta_description) }}</textarea>
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
                    <input type="text" name="duration" class="form-control"
                        placeholder="45 minutes / 1–1.5 hours / Full day"
                        value="{{ old('duration', $activity->duration) }}">
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
                    <label class="mb-4 d-block">
                        <strong>Açıklama</strong>
                    </label>

                    <div id="activity-editor" style="min-height:300px">
                        {!! old('description', $activity->description) !!}
                    </div>

                    <input type="hidden" name="description" id="descriptionInput">
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

@endsection
@section('scripts')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

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
@endsection
