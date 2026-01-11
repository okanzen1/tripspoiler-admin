@extends('layouts.admin')

@section('content')
<div class="container py-4">

    <h3 class="mb-3">Blog Aboneleri</h3>

    {{-- YENİ ABONE EKLEME FORMU --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('blog-subscribers.store') }}">
                @csrf

                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">E-posta Adresi</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="ornek@mail.com"
                            required
                        >

                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">
                            Abone Ekle
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- BAŞARILI MESAJ --}}
    @if(session('success'))
        <div class="alert alert-success small">
            {{ session('success') }}
        </div>
    @endif

    {{-- ABONE LİSTESİ --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>E-posta</th>
                            <th>Durum</th>
                            <th>Abone Olma Tarihi</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($subscribers as $subscriber)
                            <tr>
                                <td>{{ $subscriber->id }}</td>

                                {{-- ŞİFRELİ OLARAK TUTULAN, DECRYPT EDİLMİŞ EMAIL --}}
                                <td>{{ $subscriber->email }}</td>

                                {{-- AKTİF / PASİF DURUM --}}
                                <td>
                                    <form method="POST"
                                          action="{{ route('blog-subscribers.update', $subscriber) }}">
                                        @csrf
                                        @method('PUT')

                                        <input
                                            type="hidden"
                                            name="status"
                                            value="{{ $subscriber->status ? 0 : 1 }}"
                                        >

                                        <button class="btn btn-sm
                                            {{ $subscriber->status ? 'btn-success' : 'btn-secondary' }}">
                                            {{ $subscriber->status ? 'Aktif' : 'Pasif' }}
                                        </button>
                                    </form>
                                </td>

                                <td>
                                    {{ $subscriber->created_at->format('d.m.Y H:i') }}
                                </td>

                                {{-- SİLME --}}
                                <td class="text-end">
                                    <form method="POST"
                                          action="{{ route('blog-subscribers.destroy', $subscriber) }}"
                                          onsubmit="return confirm('Bu aboneyi silmek istediğinize emin misiniz?')">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-danger">
                                            Sil
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Henüz kayıtlı abone bulunmuyor
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SAYFALAMA --}}
    <div class="mt-3">
        {{ $subscribers->links() }}
    </div>

</div>
@endsection
