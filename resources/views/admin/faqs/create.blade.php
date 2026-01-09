@extends('layouts.admin')

@section('title', 'Yeni FAQ')

@section('content')

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('faqs.store') }}">
                @csrf

                <div class="mb-3">
                    <label>Soru</label>
                    <input name="question" class="form-control" required>
                </div>

                <div class="text-end">
                    <button class="btn btn-primary">Kaydet</button>
                </div>
            </form>

        </div>
    </div>

@endsection
