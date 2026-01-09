@extends('layouts.admin')

@section('title', 'Yeni Affiliate Partner')

@section('content')

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('affiliate-partners.store') }}">
                @csrf

                @if ($errors->any())
                    <div class="alert alert-danger small">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label>Partner Adı</label>
                    <input name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="active" class="form-select">
                        <option value="1">Aktif</option>
                        <option value="0">Pasif</option>
                    </select>
                </div>

                <button class="btn btn-primary">
                    Kaydet
                </button>

            </form>

        </div>
    </div>

@endsection
