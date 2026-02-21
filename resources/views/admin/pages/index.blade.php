@extends('layouts.admin')

@section('title', 'Sayfalar')

@section('content')

    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0 fw-bold">Sayfalar</h3>

            <a href="{{ route('pages.create') }}" class="btn btn-primary btn-sm">
                + Yeni Sayfa
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="80">#</th>
                            <th>Slug</th>
                            <th class="text-end" width="160">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pages as $page)
                            <tr>
                                <td class="text-muted">#{{ $page->id }}</td>
                                <td class="fw-semibold">{{ $page->slug }}</td>
                                <td class="text-end">
                                    <a href="{{ route('pages.edit', $page) }}" class="btn btn-sm btn-outline-primary">
                                        Düzenle
                                    </a>

                                    {{-- <form action="{{ route('pages.destroy', $page) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Silinsin mi?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            Sil
                                        </button>
                                    </form> --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    Henüz sayfa yok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

@endsection
