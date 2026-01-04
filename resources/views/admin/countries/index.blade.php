@extends('layouts.admin')

@section('title', 'Countries')

@section('content')

    <div class="container py-3">

        <div class="d-flex justify-content-between mb-3">
            <h3 class="m-0">Countries</h3>

            <a href="{{ route('countries.create') }}" class="btn btn-primary btn-sm">
                Yeni Ülke
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
                            <th>Ülke</th>
                            <th>Slug</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($countries as $c)
                            <tr>
                                <td>{{ $c->id }}</td>

                                <td>{{ $c->name['en'] ?? '' }}</td>

                                <td>{{ $c->slug['en'] ?? '' }}</td>

                                <td>
                                    <span class="badge {{ $c->active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $c->active ? 'Aktif' : 'Pasif' }}
                                    </span>
                                </td>

                                <td class="text-end">

                                    <a href="{{ route('countries.edit', $c) }}" class="btn btn-sm btn-outline-primary">
                                        Düzenle
                                    </a>

                                    <form method="POST" action="{{ route('countries.destroy', $c) }}" class="d-inline"
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
                                <td colspan="5" class="text-center py-4">Kayıt yok</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>

            <div class="card-footer">
                {{ $countries->links() }}
            </div>
        </div>

    </div>

@endsection
