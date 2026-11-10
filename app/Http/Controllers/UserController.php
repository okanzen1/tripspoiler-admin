<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\ImageUploader;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private ImageUploader $uploader) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $role = Role::tryFrom((string) $request->get('role'));

        // Ad, soyad, e-posta şifreli: arama ve sıralama çözülmüş hallerinde (panel kullanıcısı az)
        $all = User::query()->when($role, fn ($q) => $q->where('role', $role))->get()
            ->when($search, fn ($users) => $users->filter(fn (User $user) => str_contains(mb_strtolower($user->full_name . ' ' . $user->email), mb_strtolower($search))))
            ->sortBy(fn (User $user) => mb_strtolower($user->full_name))->values();
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $users = (new \Illuminate\Pagination\LengthAwarePaginator($all->forPage($page, 20)->values(), $all->count(), 20, $page, ['path' => $request->url()]))
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'role'));
    }

    public function create()
    {
        return view('admin.users.create', ['user' => new User(['role' => Role::Editor])]);
    }

    public function store(UserRequest $request)
    {
        $data = $request->safe()->except(['avatar', 'remove_avatar']);

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $this->uploader->avatar($request->file('avatar'));
        }

        User::create($data);

        return redirect()->route('users.index')->with('success', __('Kullanıcı oluşturuldu.'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user)
    {
        $data = $request->safe()->except(['avatar', 'remove_avatar']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $this->uploader->avatar($request->file('avatar'), replace: $user->avatar_path);
        } elseif ($request->boolean('remove_avatar')) {
            $this->uploader->delete($user->avatar_path);
            $data['avatar_path'] = null;
        }

        // Kendi rolünü düşürüp panelden kilitlenmesin
        if ($user->is($request->user()) && Role::from($data['role']) !== $user->role) {
            return back()->withInput()->with('error', __('Kendi rolünü değiştiremezsin.'));
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', __('Kullanıcı güncellendi.'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', __('Kendi hesabını silemezsin.'));
        }

        $this->uploader->delete($user->avatar_path);
        $user->delete();

        return redirect()->route('users.index')->with('success', __('Kullanıcı silindi.'));
    }
}
