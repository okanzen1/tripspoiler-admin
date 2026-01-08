@extends('layouts.admin')

@section('title', 'Edit Museum')

@section('content')

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

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('museums.update', $museum) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>İsim</label>
                    <input name="name" value="{{ $museum->name ?? '' }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Şehir</label>
                    <select name="city_id" class="form-select">
                        <option value="">- yok -</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected($museum->city_id == $city->id)>
                                {{ $city->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Ülke</label>
                    <input class="form-control" value="{{ $museum->country->name ?? '' }}" disabled>
                </div>

                <div class="mb-3">
                    <label>Sıra</label>
                    <input name="sort_order" value="{{ $museum->sort_order }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="status" class="form-select">
                        <option value="1" @selected($museum->status)>Aktif</option>
                        <option value="0" @selected(!$museum->status)>Pasif</option>
                    </select>
                </div>

                <button class="btn btn-primary">Update</button>

            </form>

            <form action="{{ route('images.upload') }}" class="dropzone mt-4" id="museum-dropzone">
                @csrf
                <input type="hidden" name="source" value="museum">
                <input type="hidden" name="source_id" value="{{ $museum->id }}">
            </form>

            <div id="sortable-museum-images" class="row mt-3">
                @foreach ($museum->images as $image)
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

        new Dropzone("#museum-dropzone", {
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

        const grid = document.getElementById('sortable-museum-images');

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
