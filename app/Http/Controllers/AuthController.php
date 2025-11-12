<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Account;
use App\Models\LoanManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function showLoginForm() { return view('auth.login'); }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->user_type === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($user->user_type === 'loan_manager' && $user->loanManager && $user->loanManager->is_active) {
                return redirect()->intended('dashboard');
            }

            Auth::logout();
            return back()->withErrors(['email' => 'Your account is not active or has been suspended. Please contact support.']);
        }

        return back()->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
    
    public function create() { return view('auth.register'); }

    
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $user = DB::transaction(function () use ($request) {
                
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'user_type' => 'loan_manager',
                ]);

                $manager = $user->loanManager()->create([
                    'company_name'      => $request->name, 
                    'phone_number'      => $request->phone_number,
                    'address'           => $request->address,
                    'is_active'         => 0, // Set to inactive by default
                    'currency_symbol'   => 'UGX', 
                ]);

                // The broken function call has been correctly removed.

                return $user;
            });

            return redirect()->route('login')->with('success', 'Registration successful! Your account is pending admin activation.');

        } catch (\Exception $e) {
            Log::error('REGISTRATION FAILED: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Registration failed due to a server error. Please try again.');
        }
    }

    // The broken 'createDefaultAccountsForManager' function has been correctly removed.
}