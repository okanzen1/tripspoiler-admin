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
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Panel sadece "Çıkış yap" ile kapanır: her girişte hatırlanır (oturum düşse bile tekrar giriş istemez)
        if (!Auth::attempt($credentials, remember: true)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('E-posta veya şifre hatalı.')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('analytics.index'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
