@extends('layouts.admin')

@section('title', 'Affiliate Partner Düzenle')

@section('content')

    <div class="card">
        <div class="card-body">

            @if (session('success'))
                <div class="alert alert-success small">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('affiliate-partners.update', $affiliate_partner) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Partner Adı</label>
                    <input name="name" value="{{ $affiliate_partner->name ?? '' }}" class="form-control" required>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="active" @checked($affiliate_partner->active)>

                    <label class="form-check-label">
                        Aktif
                    </label>
                </div>

                <button class="btn btn-primary">
                    Güncelle
                </button>

            </form>

        </div>
    </div>

@endsection
