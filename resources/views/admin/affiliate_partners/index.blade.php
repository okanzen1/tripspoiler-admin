@extends('layouts.admin')

@section('title', 'Affiliate Partners')

@section('content')

    <div class="container py-3">

        <div class="d-flex justify-content-between mb-3">
            <h3 class="m-0">Affiliate Partners</h3>

            <a href="{{ route('affiliate-partners.create') }}" class="btn btn-primary btn-sm">
                Yeni Partner
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
                            <th>Ad</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($partners as $p)
                            <tr>
                                <td>{{ $p->id }}</td>

                                <td>{{ $p->name['en'] ?? '' }}</td>

                                <td>
                                    @if ($p->active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Pasif</span>
                                    @endif
                                </td>

                                <td class="text-end">

                                    <a href="{{ route('affiliate-partners.edit', $p) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Düzenle
                                    </a>

                                    <form action="{{ route('affiliate-partners.destroy', $p) }}" method="POST"
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
                                <td colspan="4" class="text-center py-4">
                                    Kayıt yok
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>

            </div>

            <div class="card-footer">
                {{ $partners->links() }}
            </div>
        </div>

    </div>

@endsection
