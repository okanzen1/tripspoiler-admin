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

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">

                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Aktivite Adı</th>
                                <th>Şehir</th>
                                <th>Ülke</th>
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
                                    <td>{{ $activity->country?->name ?? '-' }}</td>
                                    <td>
                                        @if ($activity->status)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Pasif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">

                                        <a href="{{ route('activities.edit', $activity) }}" class="btn btn-sm btn-outline-primary">
                                            Düzenle
                                        </a>

                                        <form action="{{ route('activities.destroy', $activity) }}" method="POST" class="d-inline"
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
