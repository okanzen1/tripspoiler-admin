@extends('layouts.admin')

@section('title', 'Yeni Blog')

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

            <form method="POST" action="{{ route('blogs.store') }}">
                @csrf

                <div class="mb-3">
                    <label>Blog Adı</label>
                    <input name="title" class="form-control" required>
                </div>

                <div class="text-end">
                    <button class="btn btn-primary">Kaydet</button>
                </div>

            </form>

        </div>
    </div>

@endsection
