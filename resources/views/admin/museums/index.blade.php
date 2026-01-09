@extends('layouts.admin')

@section('content')
    <div class="container py-3">

        <div class="d-flex justify-content-between mb-3">
            <h3>Müzeler</h3>

            <a href="{{ route('museums.create') }}" class="btn btn-primary btn-sm">
                Yeni Müze
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
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

        <div class="card">
            <div class="table-responsive">

                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>City</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($museums as $museum)
                            <tr>
                                <td>{{ $museum->id }}</td>

                                <td>{{ $museum->name ?? '' }}</td>

                                <td>{{ $museum->city->name ?? '-' }}</td>

                                <td>{{ $museum->sort_order }}</td>

                                <td>
                                    @if ($museum->status)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Passive</span>
                                    @endif
                                </td>

                                <td class="text-end">

                                    <a href="{{ route('museums.edit', $museum) }}" class="btn btn-sm btn-outline-primary">
                                        Edit
                                    </a>

                                    <form action="{{ route('museums.destroy', $museum) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete?');">

                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-outline-danger">
                                            Delete
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>

            <div class="card-footer">
                {{ $museums->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>
@endsection
