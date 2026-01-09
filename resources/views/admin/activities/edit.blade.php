@extends('layouts.admin')

@section('title', 'Aktivite Düzenle')

@section('content')

    <div class="card">
        <div class="card-body">

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

            <form method="POST" action="{{ route('activities.update', $activity) }}">
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
                    <label>Müze</label>
                    <select name="museum_id" class="form-select">
                        <option value="">- yok -</option>
                        @foreach ($museums as $museum)
                            <option value="{{ $museum->id }}" @selected(old('museum_id', $activity->museum_id) == $museum->id)>
                                {{ $museum->name }}
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
                    <label>İş Ortağı Linki</label>
                    <input type="url" name="affiliate_link" value="{{ old('affiliate_link', $activity->affiliate_link) }}"
                        class="form-control">
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

                <button class="btn btn-primary">Güncelle</button>

            </form>

            <form action="{{ route('images.upload') }}" class="dropzone mt-4" id="activity-dropzone">
                @csrf
                <input type="hidden" name="source" value="activity">
                <input type="hidden" name="source_id" value="{{ $activity->id }}">
            </form>

            <div id="sortable-images" class="row mt-3">
                @foreach ($activity->images as $image)
                    <div class="col-md-3 mb-2" data-id="{{ $image->id }}">
                        <div class="border rounded p-1">
                            <img src="{{ route('images.view', $image) }}" class="img-fluid">
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
@endsection
