@extends('layouts.admin')

@section('content')

    <div class="container py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0">Bloglar</h3>

            <a href="{{ route('blogs.create') }}" class="btn btn-primary btn-sm">
                Yeni Blog
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
                                <th>Blog Adı</th>
                                <th>Şehir</th>
                                <th>Durum</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($blogs as $blog)
                                <tr>
                                    <td>{{ $blog->id }}</td>

                                    <td>
                                        {{ $blog->getTranslation('title', app()->getLocale()) }}
                                    </td>

                                    <td>
                                        {{ $blog->city?->name ?? '-' }}
                                    </td>

                                    <td>
                                        @if ($blog->status)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Pasif</span>
                                        @endif
                                    </td>

                                    <td class="text-end">

                                        <a href="{{ route('blogs.edit', $blog) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Düzenle
                                        </a>

                                        <form action="{{ route('blogs.destroy', $blog) }}"
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
                                    <td colspan="5" class="text-center py-4">
                                        Hiç blog bulunamadı.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>

                </div>
            </div>

            <div class="card-footer">
                {{ $blogs->links('pagination::bootstrap-5') }}
            </div>
        </div>

    </div>

@endsection
