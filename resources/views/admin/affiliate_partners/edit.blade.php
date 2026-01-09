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

                <div class="mb-3">
                    <label>Durum</label>
                    <select name="active" class="form-select">
                        <option value="1" {{ old('active', $affiliate_partner->active) == 1 ? 'selected' : '' }}>
                            Aktif
                        </option>
                        <option value="0" {{ old('active', $affiliate_partner->active) == 0 ? 'selected' : '' }}>
                            Pasif
                        </option>
                    </select>
                </div>

                <div class="text-end">
                    <button class="btn btn-primary">Güncelle</button>
                </div>
            </form>

        </div>
    </div>

@endsection
