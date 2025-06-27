<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LoanManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => 'loan_manager',
        ]);

        LoanManager::create([
            'user_id' => $user->id,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'is_active' => false,
        ]);

        return redirect('/login')->with('status', 'Registration successful! Please wait for an Admin to activate your account.');
    }

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle user login.
     * This version includes the Super Admin redirection.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        
        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->user_type === 'loan_manager') {
            $loanManager = $user->loanManager; 
            if (!$loanManager || !$loanManager->is_active) {
                return back()->withErrors([
                    'email' => 'Your account is inactive. Please contact the Admin for activation on 0740859082.'
                ])->onlyInput('email');
            }
        }
        
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Check the user's type and redirect them accordingly.
            $authenticatedUser = Auth::user(); 

            if ($authenticatedUser->user_type === 'super_admin') {
                // If they are a super admin, send them to the admin dashboard route.
                return redirect()->route('admin.dashboard');
            } else {
                // Otherwise, send them to the regular loan manager dashboard.
                return redirect()->intended('dashboard');
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle user logout (for web routes).
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}