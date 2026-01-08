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

        </div>
    </div>

@endsection
