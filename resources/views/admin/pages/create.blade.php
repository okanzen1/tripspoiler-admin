@extends('layouts.admin')

@section('title', 'Yeni Sayfa')

@section('content')

<div class="container py-4">

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

            <form method="POST" action="{{ route('pages.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input name="slug"
                           class="form-control"
                           placeholder="activities"
                           required>
                </div>

                <button class="btn btn-primary">
                    Kaydet
                </button>

            </form>

        </div>
    </div>

</div>

@endsection
