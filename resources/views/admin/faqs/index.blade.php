@extends('layouts.admin')

@section('content')

<div class="container py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">Sıkça Sorulan Sorular</h3>

        <a href="{{ route('faqs.create') }}" class="btn btn-primary btn-sm">
            Yeni Soru
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success small">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Soru</th>
                        <th>Kaynak</th>
                        <th>Kaynak ID</th>
                        <th>Durum</th>
                        <th>Sıra</th>
                        <th class="text-end">İşlem</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($faqs as $faq)
                    <tr>
                        <td>{{ $faq->id }}</td>

                        <td>{{ $faq->question['en'] }}</td>

                        <td>{{ $faq->source ?? '-' }}</td>

                        <td>{{ $faq->source_id ?? '-' }}</td>

                        <td>
                            @if($faq->status)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Pasif</span>
                            @endif
                        </td>

                        <td>{{ $faq->sort_order }}</td>

                        <td class="text-end">

                            <a href="{{ route('faqs.edit',$faq) }}" class="btn btn-sm btn-outline-primary">
                                Düzenle
                            </a>

                            <form action="{{ route('faqs.destroy',$faq) }}"
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
                @endforeach
                </tbody>

            </table>
        </div>

        <div class="card-footer">
            {{ $faqs->links('pagination::bootstrap-5') }}
        </div>
    </div>

</div>

@endsection
