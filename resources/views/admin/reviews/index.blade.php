@extends('layouts.admin')

@section('title', 'Yorumlar')

@section('content')

    <div class="container py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h3 class="m-0">Yorumlar</h3>

            <a href="{{ route('reviews.create') }}" class="btn btn-primary btn-sm">
                Yeni Yorum
            </a>

        </div>

        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif


        <div class="card">

            <div class="table-responsive">

                <table class="table mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>#</th>
                            <th>İsim</th>
                            <th>Email</th>
                            <th>Kaynak</th>
                            <th>Puan</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($reviews as $review)
                            <tr>

                                <td>{{ $review->id }}</td>

                                <td>{{ $review->name }}</td>

                                <td>{{ $review->email }}</td>

                                <td>{{ $review->source }}</td>

                                <td>{{ $review->rating }}</td>

                                <td>

                                    @if ($review->approved)
                                        <span class="badge bg-success">
                                            Yayında
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Bekliyor
                                        </span>
                                    @endif

                                </td>

                                <td class="text-end">

                                    <a href="{{ route('reviews.edit', $review) }}" class="btn btn-sm btn-outline-primary">

                                        Düzenle

                                    </a>

                                    <form action="{{ route('reviews.destroy', $review) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Silinsin mi?')">

                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-outline-danger">
                                            Sil
                                        </button>

                                    </form>

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

            <div class="card-footer">

                {{ $reviews->links('pagination::bootstrap-5') }}

            </div>

        </div>

    </div>

@endsection
