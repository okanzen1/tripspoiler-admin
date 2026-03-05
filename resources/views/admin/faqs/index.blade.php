@extends('layouts.admin')

@section('content')
    <div class="container py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0">Sıkça Sorulan Sorular</h3>

            <a href="{{ route('faqs.create') }}" class="btn btn-primary btn-sm">
                Yeni Soru
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">

                <form method="GET" action="{{ route('faqs.index') }}">
                    <div class="row g-2">

                        <div class="col-md-3">
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="form-control form-control-sm" placeholder="Soru ara...">
                        </div>

                        <div class="col-md-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Durum</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pasif</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select name="source" class="form-select form-select-sm">
                                <option value="">Kaynak</option>

                                @foreach ($sources as $key => $label)
                                    <option value="{{ $key }}" {{ request('source') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <div class="col-md-2">
                            <input type="number" name="source_id" value="{{ request('source_id') }}"
                                class="form-control form-control-sm" placeholder="Kaynak ID">
                        </div>

                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                Filtrele
                            </button>

                            <a href="{{ route('faqs.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                                Temizle
                            </a>
                        </div>

                    </div>
                </form>

            </div>
        </div>

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
                        @foreach ($faqs as $faq)
                            <tr>
                                <td>{{ $faq->id }}</td>

                                <td>{{ Str::limit($faq->question, 10) }}</td>

                                <td>
                                    {{ $sources[$faq->source] ?? '-' }}
                                </td>

                                <td>

                                    @if ($faq->source === 'activity-show' && $faq->source_id)

                                        <a href="{{ url('/activities/'.$faq->source_id.'/edit') }}" class="text-decoration-none">
                                            {{ \Illuminate\Support\Str::limit($activityMap[$faq->source_id] ?? '-', 35) }}
                                        </a>

                                    @elseif ($faq->source === 'blog-show' && $faq->source_id)

                                        <a href="{{ url('/blogs/'.$faq->source_id.'/edit') }}" class="text-decoration-none">
                                            {{ \Illuminate\Support\Str::limit($blogMap[$faq->source_id] ?? '-', 35) }}
                                        </a>

                                    @else

                                        {{ $faq->source_id ?? '-' }}

                                    @endif

                                </td>

                                <td>
                                    @if ($faq->status)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Pasif</span>
                                    @endif
                                </td>

                                <td>{{ $faq->sort_order }}</td>

                                <td class="text-end">

                                    <a href="{{ route('faqs.edit', $faq) }}" class="btn btn-sm btn-outline-primary">
                                        Düzenle
                                    </a>

                                    <form action="{{ route('faqs.destroy', $faq) }}" method="POST" class="d-inline"
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
