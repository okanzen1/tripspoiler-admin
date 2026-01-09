@extends('layouts.admin')

@section('title', 'Şehir Düzenle')

@section('content')

    <div class="card">
        <div class="card-body">

            @if (session('success'))
                <div class="alert alert-success small">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('cities.update', $city) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Ülke</label>
                    <select name="country_id" class="form-select">
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected($city->country_id == $country->id)>
                                {{ $country->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Şehir Adı</label>
                    <input name="name" value="{{ $city->name ?? '' }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <input name="slug" value="{{ $city->slug ?? '' }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="active" class="form-select">
                        <option value="1" {{ old('active', $city->active) == 1 ? 'selected' : '' }}>
                            Aktif
                        </option>
                        <option value="0" {{ old('active', $city->active) == 0 ? 'selected' : '' }}>
                            Pasif
                        </option>
                    </select>
                </div>

                <button class="btn btn-primary">
                    Güncelle
                </button>

            </form>

            <form action="{{ route('images.upload') }}" class="dropzone mt-4" id="city-dropzone">
                @csrf
                <input type="hidden" name="source" value="city">
                <input type="hidden" name="source_id" value="{{ $city->id }}">
            </form>

            <div id="sortable-city-images" class="row mt-3">
                @foreach ($city->images as $image)
                    <div class="col-md-3 mb-2" data-id="{{ $image->id }}">
                        <div class="border rounded p-1">
                            <img src="{{ route('images.view', $image) }}" class="img-fluid rounded">

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
        Dropzone.autoDiscover = false;

        new Dropzone("#city-dropzone", {
            maxFilesize: 2,
            acceptedFiles: 'image/*',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            success() {
                location.reload();
            }
        });

        const grid = document.getElementById('sortable-city-images');

        new Sortable(grid, {
            animation: 150,
            onEnd() {
                const order = [];
                grid.querySelectorAll('[data-id]')
                    .forEach(el => order.push(el.dataset.id));

                fetch("{{ route('images.sort') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        order
                    })
                });
            }
        });

        document.querySelectorAll('.delete-image').forEach(btn => {
            btn.onclick = () => {
                if (!confirm('Silinsin mi?')) return;

                fetch(btn.dataset.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                }).then(() => location.reload());
            };
        });
    </script>
@endsection
