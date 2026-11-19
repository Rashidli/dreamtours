<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login_submit(LoginRequest $request)
    {

        // "Keep me logged in" is optional; a fresh session id after login prevents session fixation.
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'password' => ['The email or password is incorrect.'],
        ]);

    }

    public function logout()
    {

        Auth::logout();

        return redirect()->route('login');

    }
}
