@extends('layouts.admin')

@section('content')

<div class="container py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">Kullanıcılar</h3>

        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
            Yeni Kullanıcı
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success small">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger small">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
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
                            <th>İsim</th>
                            <th>Kullanıcı Adı</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->role == 'admin')
                                    <span class="badge bg-primary">
                                        Admin
                                    </span>
                                @elseif($user->role == 'super_admin')
                                    <span class="badge bg-success">
                                        Super Admin
                                    </span>
                                @elseif($user->role == 'user')
                                    <span class="badge bg-secondary">
                                        User
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                <a href="{{ route('users.edit',$user) }}" class="btn btn-sm btn-outline-primary">
                                    Düzenle
                                </a>

                                <form action="{{ route('users.destroy',$user) }}"
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
                            <td colspan="6" class="text-center py-4">
                                Kullanıcı bulunamadı
                            </td>
                        </tr>
                    @endforelse
                    </tbody>

                </table>

            </div>
        </div>

        <div class="card-footer">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    </div>

</div>

@endsection
