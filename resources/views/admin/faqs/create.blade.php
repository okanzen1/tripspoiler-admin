@extends('layouts.admin')

@section('title','Yeni FAQ')

@section('content')

<div class="card">
    <div class="card-body">

        <form method="POST" action="{{ route('faqs.store') }}">
            @csrf

            <div class="mb-3">
                <label>Soru</label>
                <input name="question" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Cevap</label>
                <textarea name="answer" class="form-control" rows="5" required></textarea>
            </div>

            <div class="mb-3">
                <label>Kaynak (Opsiyonel)</label>
                <input name="source" class="form-control" placeholder="ör: product, city, pass">
            </div>

            <div class="mb-3">
                <label>Kaynak ID (Opsiyonel)</label>
                <input name="source_id" class="form-control" placeholder="ör: 15">
            </div>

            <div class="mb-3">
                <label>Sıra</label>
                <input name="sort_order" class="form-control" value="0">
            </div>

            <div class="mb-3">
                <label>Durum</label>
                <select name="status" class="form-select">
                    <option value="1">Aktif</option>
                    <option value="0">Pasif</option>
                </select>
            </div>

            <button class="btn btn-primary">Kaydet</button>
        </form>

    </div>
</div>

@endsection
