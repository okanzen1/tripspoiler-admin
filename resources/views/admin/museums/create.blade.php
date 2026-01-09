@extends('layouts.admin')

@section('title', 'New Museum')

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

            <form method="POST" action="{{ route('museums.store') }}">
                @csrf

                <div class="mb-3">
                    <label>İsim</label>
                    <input name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Şehir</label>
                    <select name="city_id" class="form-select">
                        <option value="">- yok -</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}">
                                {{ $city->name ?? '' }}
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
