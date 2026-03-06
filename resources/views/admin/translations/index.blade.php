@extends('layouts.admin')

@section('title', 'Dil Yönetimi')

@section('content')

    <div class="container py-4">

        <h3 class="mb-4">Çeviri Yönetimi</h3>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- DeepL Kullanım --}}
        <div class="card mb-4">
            <div class="card-body">

                <h5 class="mb-3">DeepL Kullanım Durumu</h5>

                <div class="row">

                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <small class="text-muted">Kullanılan</small>
                            <h4 class="mb-0">
                                {{ number_format($used) }}
                            </h4>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <small class="text-muted">Limit</small>
                            <h4 class="mb-0">
                                {{ number_format($limit) }}
                            </h4>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <small class="text-muted">Kalan</small>
                            <h4 class="mb-0 text-success">
                                {{ number_format($remaining) }}
                            </h4>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        {{-- Dil Ekle --}}
        <div class="card mb-4">
            <div class="card-body">

                <form method="POST" action="{{ route('translators.store') }}">
                    @csrf

                    <div class="row">

                        <div class="col-md-6">

                            <label>DeepL Dil Seç</label>

                            <select name="code" class="form-select">

                                @foreach ($deeplLanguages as $lang)
                                    <option value="{{ strtolower($lang['language']) }}">

                                        {{ $lang['name'] ?? $lang['language'] }}

                                        ({{ $lang['language'] }})
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        <div class="col-md-2 d-flex align-items-end">

                            <button class="btn btn-primary w-100">
                                Dil Ekle
                            </button>

                        </div>

                    </div>

                </form>

            </div>
        </div>

        {{-- Dil Listesi --}}
        <div class="card">
            <div class="card-body">

                <table class="table table-bordered">

                    <thead>
                        <tr>
                            <th width="120">Kod</th>
                            <th>Dil İsmi</th>
                            <th width="150">Durum</th>
                            <th width="120">Sil</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($languages as $lang)
                            <tr>

                                <td>{{ strtoupper($lang->code) }}</td>

                                <td>{{ $lang->name }}</td>

                                <td>

                                    <form method="POST" action="{{ route('translators.toggle', $lang) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            class="btn btn-sm {{ $lang->active ? 'btn-success' : 'btn-outline-secondary' }}">

                                            {{ $lang->active ? 'Aktif' : 'Pasif' }}

                                        </button>

                                    </form>

                                </td>

                                <td>

                                    <form method="POST" action="{{ route('translators.destroy', $lang) }}"
                                        onsubmit="return confirm('Silinsin mi?')">

                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-danger">
                                            Sil
                                        </button>

                                    </form>

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>
        </div>

    </div>

@endsection
