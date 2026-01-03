<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors(['username' => 'Hatalı bilgiler']);
        }

        if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) {
            Auth::logout();

            return back()->withErrors(['username' => 'Yetkin yok']);
        }

        return redirect()->route('admin.dashboard');
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('admin.login');
    }
}
