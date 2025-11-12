<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $messages = BroadcastMessage::latest()->get();
        return view('admin.broadcasts.index', compact('messages')); // *** ADDED: Index view for managing ***
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
            // is_active defaults to false
        ]);

        return redirect()->route('admin.broadcasts.index') // *** CHANGED: Redirect to index page for management ***
                         ->with('status', 'Broadcast message created. Remember to activate it.');
    }

    /**
     * Toggle the active status of a message.
     */
    public function toggle(BroadcastMessage $broadcast)
    {
        // If this one is being activated, deactivate all others
        if (!$broadcast->is_active) {
            BroadcastMessage::where('id', '!=', $broadcast->id)->update(['is_active' => false]);
        }
        
        // Toggle the current one
        $broadcast->update(['is_active' => !$broadcast->is_active]);

        $status = $broadcast->is_is_active ? 'activated' : 'deactivated';
        return back()->with('status', "Message '$broadcast->title' has been $status.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BroadcastMessage $broadcast)
    {
        $broadcast->delete();
        return back()->with('status', 'Message deleted.');
    }
}