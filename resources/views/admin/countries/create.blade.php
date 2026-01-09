@extends('layouts.admin')

@section('title', 'Yeni Ülke')

@section('content')

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('countries.store') }}">
                @csrf

                <div class="mb-3">
                    <label>Ülke Adı</label>
                    <input name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <input name="slug" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="active" class="form-select">
                        <option value="1" {{ old('active', 1) == 1 ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ old('active', 1) == 0 ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>

                <div class="text-end">
                    <button class="btn btn-primary">Kaydet</button>
                </div>

            </form>

        </div>
    </div>

@endsection
