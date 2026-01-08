@extends('layouts.admin')

@section('title', 'Cities')

@section('content')

    <div class="container py-3">

        <div class="d-flex justify-content-between mb-3">
            <h3 class="m-0">Şehirler</h3>

            <a href="{{ route('cities.create') }}" class="btn btn-primary btn-sm">
                Yeni Şehir
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <div class="card-body p-0">

                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Şehir</th>
                            <th>Ülke</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($cities as $c)
                            <tr>
                                <td>{{ $c->id }}</td>
                                <td>{{ $c->name ?? '' }}</td>
                                <td>{{ $c->country?->name ?? '' }}</td>

                                <td>
                                    <span class="badge {{ $c->active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $c->active ? 'Aktif' : 'Pasif' }}
                                    </span>
                                </td>

                                <td class="text-end">

                                    <a href="{{ route('cities.edit', $c) }}" class="btn btn-sm btn-outline-primary">
                                        Düzenle
                                    </a>

                                    <form action="{{ route('cities.destroy', $c) }}" method="POST" class="d-inline"
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
                                <td colspan="5" class="text-center py-3">
                                    Kayıt bulunamadı
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>

            <div class="card-footer">
                {{ $cities->links('pagination::bootstrap-5') }}
            </div>
        </div>

    </div>

@endsection
