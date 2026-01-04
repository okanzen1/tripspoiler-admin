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

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="active" checked>
                    <label class="form-check-label">
                        Aktif
                    </label>
                </div>

                <button class="btn btn-primary">
                    Kaydet
                </button>

            </form>

        </div>
    </div>

@endsection
