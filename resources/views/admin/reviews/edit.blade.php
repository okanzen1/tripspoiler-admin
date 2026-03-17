@extends('layouts.admin')

@section('title', 'Yorum Düzenle')

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

            <form method="POST" action="{{ route('reviews.update', $review) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_to" value="{{ request('return_to') }}">
                
                <div class="mb-3">

                    <label>İsim</label>
                    <input name="name" class="form-control" value="{{ old('name', $review->name) }}">

                </div>


                <div class="mb-3">

                    <label>Email</label>
                    <input name="email" type="email" class="form-control" value="{{ old('email', $review->email) }}">

                </div>


                <div class="mb-3">

                    <label>Puan</label>

                    <select name="rating" class="form-select">

                        <option value="5" {{ $review->rating == 5 ? 'selected' : '' }}>5</option>
                        <option value="4" {{ $review->rating == 4 ? 'selected' : '' }}>4</option>
                        <option value="3" {{ $review->rating == 3 ? 'selected' : '' }}>3</option>
                        <option value="2" {{ $review->rating == 2 ? 'selected' : '' }}>2</option>
                        <option value="1" {{ $review->rating == 1 ? 'selected' : '' }}>1</option>

                    </select>

                </div>


                <div class="mb-3">

                    <label>Yorum</label>

                    <textarea name="comment" class="form-control" rows="4" placeholder="Yorum yaz...">{{ old('comment', $review->comment) }}</textarea>

                </div>

                <div class="mb-3">
                    <label>Kaynak</label>

                    <select class="form-select" disabled>
                        @foreach ($sources as $key => $label)
                            <option value="{{ $key }}" @selected($review->source == $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <input type="hidden" name="source" value="{{ $review->source }}">
                </div>

                <div class="mb-3">
                    @if ($review->source === 'activity-show')
                        <label>Kaynak</label>
                        <select name="source_id" class="form-select source-select">
                            <option value="">Activity seç</option>

                            @foreach ($activities as $activity)
                                <option value="{{ $activity->id }}" @selected($review->source_id == $activity->id)>
                                    {{ $activity->id }} - {{ $activity->name }}
                                </option>
                            @endforeach

                        </select>
                    @elseif ($review->source === 'blog-show')
                        <label>Kaynak</label>
                        <select name="source_id" class="form-select source-select">
                            <option value="">Blog seç</option>

                            @foreach ($blogs as $blog)
                                <option value="{{ $blog->id }}" @selected($review->source_id == $blog->id)>
                                    {{ $blog->id }} - {{ $blog->title }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                </div>


                <div class="mb-3">

                    <label>Durum</label>

                    <select name="approved" class="form-select">

                        <option value="1" {{ $review->approved ? 'selected' : '' }}>
                            Yayında
                        </option>

                        <option value="0" {{ !$review->approved ? 'selected' : '' }}>
                            Bekliyor
                        </option>

                    </select>

                </div>


                <div class="text-end">

                    <button class="btn btn-primary">
                        Güncelle
                    </button>

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
