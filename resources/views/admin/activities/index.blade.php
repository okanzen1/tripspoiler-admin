@extends('layouts.admin')

@section('content')

    <div class="container py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0">Aktiviteler</h3>

            <a href="{{ route('activities.create') }}" class="btn btn-primary btn-sm">
                Yeni Aktivite
            </a>
        </div>

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

        <form method="GET" class="row g-2 mb-3">

            {{-- Şehir --}}
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

            {{-- Durum --}}
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="1" {{ (int) request('status', 1) === 1 ? 'selected' : '' }}>
                        Aktif
                    </option>
                    <option value="0" {{ (int) request('status', 1) === 0 ? 'selected' : '' }}>
                        Pasif
                    </option>
                </select>
            </div>

            {{-- Butonlar --}}
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary">Filtrele</button>
                <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary">
                    Sıfırla
                </a>
            </div>

        </form>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">

                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Aktivite Adı</th>
                                <th>Şehir</th>
                                <th>Durum</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($activities as $activity)
                                <tr>
                                    <td>{{ $activity->id }}</td>
                                    <td>{{ $activity->name }}</td>
                                    <td>{{ $activity->city?->name ?? '-' }}</td>
                                    <td>
                                        @if ($activity->status)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Pasif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">

                                        <a href="{{ route('activities.edit', $activity) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Düzenle
                                        </a>

                                        <form action="{{ route('activities.destroy', $activity) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Silinsin mi?');">

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
                                    <td colspan="6" class="text-center py-4">
                                        Hiç aktivite bulunamadı.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>

                </div>
            </div>

            <div class="card-footer">
                {{ $activities->links('pagination::bootstrap-5') }}
            </div>
        </div>

    </div>

@endsection
