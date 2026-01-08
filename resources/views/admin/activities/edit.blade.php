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
                    <label>Ülke</label>
                    <input class="form-control" value="{{ $activity->country?->name }}" disabled>
                </div>

                <div class="mb-3">
                    <label>Şehir</label>
                    <input class="form-control" value="{{ $activity->city?->name }}" disabled>
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

        </div>
    </div>

@endsection
