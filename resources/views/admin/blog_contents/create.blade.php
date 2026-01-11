@extends('layouts.admin')

@section('title', 'Yeni İçerik')

@section('content')

<div class="card">
    <div class="card-body">

        <form method="POST" action="{{ route('blogs.content.store', $blog) }}">
            @csrf

             <div class="mb-3">
                <label>Blog Adı</label>
                <input name="title" class="form-control" required>
            </div>

            <button class="btn btn-primary">
                Kaydet
            </button>

        </form>

    </div>
</div>

@endsection
