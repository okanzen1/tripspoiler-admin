@extends('layouts.admin')

@section('title', 'Yeni FAQ')

@section('content')

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('faqs.store') }}">
                @csrf
                <input type="hidden" name="return_to" value="{{ request('return_to') }}">
                <div class="mb-3">
                    <label>Soru</label>
                    <input name="question" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Kaynak</label>

                    <select name="source" class="form-select" required>
                        <option value="">Seçiniz</option>

                        @foreach ($sources as $key => $label)
                            <option value="{{ $key }}" @selected(($defaultSource ?? '') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="source_id" value="{{ $defaultSourceId ?? request('source_id') }}">

                <div class="text-end">
                    <button class="btn btn-primary">Kaydet</button>
                </div>

            </form>

        </div>
    </div>

@endsection
