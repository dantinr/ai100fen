<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(Request $request): View
    {
        $returnTo = $request->query('redirect');
        if (is_string($returnTo) && preg_match('#\A/lab/[a-z0-9-]+/[a-z0-9-]+\z#', $returnTo)) {
            $request->session()->put('url.intended', url($returnTo));
        }

        return view('frontend.auth', ['register' => false]);
    }

    public function register(): View
    {
        return view('frontend.auth', ['register' => true]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('me'));
    }

    public function authenticate(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('me'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
