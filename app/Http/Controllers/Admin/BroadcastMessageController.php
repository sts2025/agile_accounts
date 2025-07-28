<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastMessageController extends Controller
{
    /**
     * Show the form for creating a new broadcast message.
     */
    public function create()
    {
        return view('admin.broadcasts.create');
    }

    /**
     * Store a newly created broadcast message in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        BroadcastMessage::create([
            'user_id' => Auth::id(), // Automatically links to the logged-in admin
            'title' => $validatedData['title'],
            'body' => $validatedData['body'],
        ]);

        return redirect()->route('admin.dashboard')
                         ->with('status', 'Broadcast message has been sent successfully!');
    }
}