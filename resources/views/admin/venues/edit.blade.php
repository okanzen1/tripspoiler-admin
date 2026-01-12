@extends('layouts.admin')

@section('title', 'Mekan Düzenle')

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

        <form method="POST" action="{{ route('venues.update', $venue) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Mekan Adı</label>
                <input name="name"
                       value="{{ old('name', $venue->name) }}"
                       class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Slug</label>
                <input name="slug"
                       value="{{ old('slug', $venue->slug) }}"
                       class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Şehir</label>
                <select name="city_id" class="form-select" required>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}"
                            @selected(old('city_id', $venue->city_id) == $city->id)>
                            {{ $city->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label>Müze</label>
                <select name="museum_id" class="form-select">
                    <option value="">- yok -</option>
                    @foreach ($museums as $museum)
                        <option value="{{ $museum->id }}"
                            @selected(old('museum_id', $venue->museum_id) == $museum->id)>
                            {{ $museum->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label>İş Ortağı</label>
                <select name="affiliate_id" class="form-select">
                    <option value="">- yok -</option>
                    @foreach ($affiliatePartners as $partner)
                        <option value="{{ $partner->id }}"
                            @selected(old('affiliate_id', $venue->affiliate_id) == $partner->id)>
                            {{ $partner->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label>Affiliate Link</label>
                <input type="url"
                       name="affiliate_link"
                       value="{{ old('affiliate_link', $venue->affiliate_link) }}"
                       class="form-control">
            </div>

            <div class="mb-3">
                <label>Durum</label>
                <select name="status" class="form-select">
                    <option value="1" @selected(old('status', $venue->status))>Aktif</option>
                    <option value="0" @selected(!old('status', $venue->status))>Pasif</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Sıralama</label>
                <input type="number"
                       name="sort_order"
                       value="{{ old('sort_order', $venue->sort_order) }}"
                       class="form-control">
            </div>

            <button type="submit"
                class="btn btn-primary"
                style="position: fixed; bottom: 60px; right: 20px; z-index: 1050;">
                Güncelle
            </button>

        </form>

    </div>
</div>

{{-- IMAGES --}}
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Mekan Görselleri</h5>
    </div>

    <div class="card-body">

        <form action="{{ route('images.upload') }}"
              class="dropzone"
              id="venue-dropzone">
            @csrf
            <input type="hidden" name="source" value="venue">
            <input type="hidden" name="source_id" value="{{ $venue->id }}">
        </form>

        <div id="sortable-images" class="row mt-3">
            @foreach ($venue->images as $image)
                <div class="col-md-3 mb-2" data-id="{{ $image->id }}">
                    <div class="border rounded p-1">
                        <img src="{{ $image->url }}" class="img-fluid">
                        <button type="button"
                                class="btn btn-danger btn-sm w-100 mt-1 delete-image"
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
