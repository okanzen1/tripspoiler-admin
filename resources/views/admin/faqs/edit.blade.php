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
                    <input name="question" value="{{ $faq->question }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Cevap</label>
                    <textarea name="answer" class="form-control" rows="5">{{ $faq->answer }}</textarea>
                </div>

                <div class="mb-3">
                    <label>Kaynak</label>

                    <select class="form-select" disabled>
                        @foreach ($sources as $key => $label)
                            <option value="{{ $key }}" @selected($faq->source === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <input type="hidden" name="source" value="{{ $faq->source }}">
                </div>

                <div class="mb-3">
                    @if ($faq->source === 'activity-show')
                        <label>Kaynak</label>
                        <select name="source_id" class="form-select source-select">
                            <option value="">Activity seç</option>

                            @foreach ($activities as $activity)
                                <option value="{{ $activity->id }}" @selected($faq->source_id == $activity->id)>
                                    {{ $activity->id }} - {{ $activity->name }}
                                </option>
                            @endforeach

                        </select>
                    @elseif ($faq->source === 'blog-show')
                        <label>Kaynak</label>
                        <select name="source_id" class="form-select source-select">
                            <option value="">Blog seç</option>

                            @foreach ($blogs as $blog)
                                <option value="{{ $blog->id }}" @selected($faq->source_id == $blog->id)>
                                    {{ $blog->id }} - {{ $blog->title }}
                                </option>
                            @endforeach
                        </select>
                    @endif

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

                <div class="text-end">
                    <button class="btn btn-primary">Kaydet</button>
                </div>
            </form>

        </div>
    </div>

@endsection
@section('scripts')

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            $('.source-select').select2({
                placeholder: "Arama yap...",
                allowClear: true,
                width: '100%'
            });

        });
    </script>
@endsection
