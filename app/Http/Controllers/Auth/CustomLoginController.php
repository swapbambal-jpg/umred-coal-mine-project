<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CustomLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.custom-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {

            $user = Auth::user();

        // Store user data in session
        session([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email
        ]);

            return redirect()->intended('/dashboard'); // Redirect to intended route
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.'
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/login');
    }
}
