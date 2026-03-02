@extends('layouts.admin')

@section('content')

<div class="container py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="m-0 fw-bold">Aktiviteler</h3>

        <a href="{{ route('activities.create') }}" class="btn btn-primary btn-sm">
            + Yeni Aktivite
        </a>
    </div>

    {{-- Alerts --}}
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

    {{-- Filters --}}
    <form method="GET" class="row g-2 mb-4">

        <div class="col-md-3">
            <select name="city_id" class="form-select">
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}"
                        {{ (int) request('city_id', 1) === $city->id ? 'selected' : '' }}>
                        {{ $city->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="1" {{ (int) request('status', 1) === 1 ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ (int) request('status', 1) === 0 ? 'selected' : '' }}>Pasif</option>
            </select>
        </div>

        <div class="col-md-2">
            <select name="most_popular" class="form-select">
                <option value="">Most Popular</option>
                <option value="1" @selected(request('most_popular') === '1')>Evet</option>
                <option value="0" @selected(request('most_popular') === '0')>Hayır</option>
            </select>
        </div>

        <div class="col-md-3">
            <select name="activity_type" class="form-select">
                <option value="">Aktivite Türü</option>
                @foreach ($productTypes as $key => $label)
                    <option value="{{ $key }}" @selected(request('activity_type') === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary w-100">Filtrele</button>
            <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary w-100">
                Sıfırla
            </a>
        </div>
    </form>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70">#</th>
                        <th>Aktivite</th>
                        <th>Sıra</th>
                        <th>Partner Urun Id</th>
                        <th>Şehir</th>
                        <th>Tür</th>
                        <th>Durum</th>
                        <th class="text-end" width="180">İşlem</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td class="text-muted small">#{{ $activity->id }}</td>

                            <td>
                                <div class="fw-semibold">{{ $activity->name }}</div>
                            </td>

                            <td>{{ $activity->sort_order ?? '-' }}</td>

                            <td>{{ $activity->source_product_id ?? '-' }}</td>

                            <td>{{ $activity->city?->name ?? '-' }}</td>

                            <td>
                                @php
                                    $typeColors = [
                                        'product' => 'primary',
                                        'pass' => 'danger',
                                        'package' => 'info',
                                    ];
                                @endphp

                                <span class="badge bg-{{ $typeColors[$activity->activity_type] ?? 'secondary' }}">
                                    {{ $productTypes[$activity->activity_type] ?? $activity->activity_type }}
                                </span>
                            </td>

                            {{-- INLINE STATUS --}}
                            <td>
                                <form action="{{ route('activities.toggle-status', $activity) }}"
                                      method="POST">
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        class="btn btn-sm {{ $activity->status ? 'btn-success' : 'btn-outline-secondary' }}">
                                        {{ $activity->status ? 'Aktif' : 'Pasif' }}
                                    </button>
                                </form>
                            </td>

                            <td class="text-end">
                                <a href="{{ route('activities.edit', $activity) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Düzenle
                                </a>

                                <form action="{{ route('activities.destroy', $activity) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Silinsin mi?');">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-outline-danger">
                                        Sil
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                Aktivite bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $activities->links('pagination::bootstrap-5') }}
        </div>
    </div>

</div>

@endsection
