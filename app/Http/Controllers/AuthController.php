<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }
// register method
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
    'required',
    'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/',
    'unique:users,email',
],
              'password' => 'required|min:8|confirmed',
], [
    'name.required' => 'Please enter your name.',

    'email.required' => 'Please enter your email address.',
    'email.regex' => 'Please enter a valid email address (e.g. name@example.com).',
    'email.unique' => 'This email address is already registered.',

    'password.required' => 'Please enter a password.',
    'password.min' => 'Password must be at least 8 characters.',
    'password.confirmed' => 'Password confirmation does not match.',
]);

        $user = User::create($validated);

        Auth::login($user);

        return redirect()->route('products.index');
    }
    public function showLogin()
{
    return view('auth.login');
}
// login method
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => [
    'required',
    'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/',
],
        'password' => 'required|min:8',
        ], [
        'email.required' => 'Please enter your email address.',
        'email.regex' => 'Please enter a valid email address (e.g. name@example.com).',
        'password.required' => 'Please enter your password.',
        'password.min' => 'Password must be at least 8 characters.',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();

        return redirect()->route('products.index');
    }

    return back()->withErrors([
        'email' => 'The email or password you entered is incorrect.',
    ])->onlyInput('email');
}
// logout method
public function logout(Request $request)
{
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
}
// api login method
public function apiLogin(Request $request)
{
    $credentials = $request->validate([
        'email' => [
            'required',
            'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/',
        ],
        'password' => 'required|min:8',
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json([
            'message' => 'The email or password you entered is incorrect.',
        ], 401);
    }

    $request->session()->regenerate();

    return response()->json([
        'message' => 'Login successful.',
        'user' => Auth::user(),
    ]);
}
}