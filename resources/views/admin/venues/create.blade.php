@extends('layouts.admin')

@section('title', 'Yeni Mekan')

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

<div class="card">
    <div class="card-body">

        <form method="POST" action="{{ route('venues.store') }}">
            @csrf

            <div class="mb-3">
                <label>Mekan Adı</label>
                <input name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Şehir</label>
                <select name="city_id" class="form-select" required>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}">
                            {{ $city->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="text-end">
                <button class="btn btn-primary">Kaydet</button>
            </div>

        </form>

    </div>
</div>

@endsection
