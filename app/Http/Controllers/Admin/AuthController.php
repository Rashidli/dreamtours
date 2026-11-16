<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login_submit(LoginRequest $request)
    {

        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
            // New session id after sign-in (prevents session fixation), then back to the page they asked for.
            $request->session()->regenerate();
            return redirect()->intended(route('home'));
        }else{
            $error = \Illuminate\Validation\ValidationException::withMessages([
                'password' => ['The password is incorrect'],
            ]);
            throw $error;
        }

    }

    public function logout()
    {

        Auth::logout();

        return redirect()->route('login');

    }
}
