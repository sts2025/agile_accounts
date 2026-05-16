<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use App\Models\User;
use Carbon\Carbon; // Ensure Carbon is imported for the expiry check

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            // 1. ADMIN CHECK (Allow ID 1 or user_type admin)
            if ($user->id === 1 || $user->user_type === 'admin') {
                session()->forget('original_admin_id');
                return redirect()->route('admin.dashboard');
            }

            // 2. LOAN MANAGER ACTIVATION & EXPIRY CHECK
            $manager = method_exists($user, 'getCompany') ? $user->getCompany() : $user->loanManager;

            if ($manager) {
                // A. Check if Subscription is Expired FIRST
                if ($manager->subscription_expires_at && Carbon::parse($manager->subscription_expires_at)->isPast()) {
                    // Turn off the active status in the database so they are completely blocked
                    $manager->is_active = 0;
                    $manager->save();
                    
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    return back()->withErrors([
                        'email' => 'Your subscription has expired. Please contact STREAMLINE TECH SOLUTION to renew.',
                    ]);
                }

                // B. Check if Admin has assigned a currency AND account is active
                if (empty($manager->currency_symbol) || $manager->is_active == 0) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'email' => 'Your account is suspended or waiting for Admin approval/activation.',
                    ]);
                }

                // Passed all checks! Let them in.
                return redirect()->intended('dashboard');
            }

            // Failsafe
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => 'Invalid account profile. Contact Admin.']);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    public function create()
    {
        return view('auth.register');
    }

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
            DB::transaction(function () use ($request) {
                
                // 1. Create User Account
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'user_type' => 'loan_manager',
                ]);

                // 2. Create Loan Manager Profile
                $user->loanManager()->create([
                    'company_name'      => $request->name, 
                    'phone_number'      => $request->phone_number,
                    'address'           => $request->address,
                    
                    // FIX FOR DB ERROR 1: Use phone as initial support phone
                    'support_phone'     => $request->phone_number, 

                    // FIX FOR DB ERROR 2: Use empty string instead of NULL.
                    'currency_symbol'   => '', 
                    'is_active'         => 0, // Force inactive initially
                ]);
            });

            return redirect()->route('login')->with('success', 'Registration successful! Please wait for the Admin to activate your account.');

        } catch (\Exception $e) {
            Log::error('REGISTRATION FAILED: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Registration failed. Please try again.');
        }
    }
}