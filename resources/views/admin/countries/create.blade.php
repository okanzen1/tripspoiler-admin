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

                <div class="form-check my-3">
                    <input type="checkbox" name="active" class="form-check-input" checked>
                    <label class="form-check-label">Aktif</label>
                </div>

                <button class="btn btn-primary">Kaydet</button>

            </form>

        </div>
    </div>

@endsection
