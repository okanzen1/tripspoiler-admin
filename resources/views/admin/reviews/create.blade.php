@extends('layouts.admin')

@section('title', 'Yeni Yorum')

@section('content')

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('reviews.store') }}">
                @csrf

                <div class="mb-3">

                    <label>İsim</label>
                    <input name="name" class="form-control" required>

                </div>

                <div class="mb-3">

                    <label>Kaynak</label>

                    <select name="source" class="form-select" required>

                        <option value="">Seçiniz</option>

                        @foreach ($sources as $key => $label)
                            <option value="{{ $key }}">
                                {{ $label }}
                            </option>
                        @endforeach

                    </select>

                </div>


                <div class="text-end">

                    <button class="btn btn-primary">
                        Kaydet
                    </button>

                </div>

            </form>

        </div>
    </div>

@endsection
