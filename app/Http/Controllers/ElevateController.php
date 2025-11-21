<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;

class ElevateController extends Controller
{
    /**
     * Show the elevation password input form.
     * Accessible via the GET request to /elevate (when triggered by middleware)
     */
    public function showForm()
    {
        // 1. Check if the user is already elevated (session flag is set).
        if (Session::get('elevated_privileges') === true) {
            // If they are, redirect them to the intended URL or the homepage.
            $intendedUrl = Session::pull('url.intended', '/');
            return Redirect::to($intendedUrl);
        }

        // 2. If not elevated, show the password input view.
        // NOTE: The name of this view must be 'elevate.form'
        return view('elevate.form');
    }

    /**
     * Handle the elevation password submission.
     * Accessible via the POST request to /elevate
     */
    public function process(Request $request)
    {
        // 1. Validate the incoming request: ensure a password was submitted.
        $request->validate([
            'elevation_password' => 'required|string',
        ]);

        // 2. Get the submitted password and the expected password from the .env file.
        $submittedPassword = $request->input('elevation_password');
        
        // Retrieve the secret key from .env (ELEVATED_PRIVILEGE_PASSWORD)
        $expectedPassword = env('ELEVATED_PRIVILEGE_PASSWORD'); 
        
        // 3. Perform a secure comparison (hash_equals prevents timing attacks).
        if (hash_equals((string) $expectedPassword, (string) $submittedPassword)) {
            // Success: Set the session flag
            Session::put('elevated_privileges', true);
            
            // Log success for auditing purposes
            Log::info('Privileges elevated successfully.', ['user_ip' => $request->ip()]);
            
            // Redirect to the intended protected page (or home)
            // Session::pull('url.intended') retrieves the original URL saved by the middleware.
            $intendedUrl = Session::pull('url.intended', '/');
            return Redirect::to($intendedUrl)
                ->with('success', 'Privileges elevated successfully!');
        } else {
            // Failure: Log the attempt and redirect back with an error.
            Log::warning('Privilege elevation failed: Invalid password.', ['user_ip' => $request->ip()]);
            
            return Redirect::back()
                ->withInput($request->only('elevation_password'))
                ->with('error', 'Invalid elevation password.');
        }
    }
}