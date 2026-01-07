@extends('layouts.admin')

@section('title', 'FAQ Düzenle')

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

            <form method="POST" action="{{ route('faqs.update', $faq) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Soru</label>
                    <input name="question" value="{{ $faq->question['en'] }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Cevap</label>
                    <textarea name="answer" class="form-control" rows="5">{{ $faq->answer['en'] }}</textarea>
                </div>

                <div class="mb-3">
                    <label>Kaynak (Opsiyonel)</label>
                    <input name="source" value="{{ $faq->source }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Kaynak ID (Opsiyonel)</label>
                    <input name="source_id" value="{{ $faq->source_id }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Sıra</label>
                    <input name="sort_order" value="{{ $faq->sort_order }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="status" class="form-select">
                        <option value="1" @selected($faq->status)>Aktif</option>
                        <option value="0" @selected(!$faq->status)>Pasif</option>
                    </select>
                </div>

                <button class="btn btn-primary">Güncelle</button>

            </form>

        </div>
    </div>

@endsection
