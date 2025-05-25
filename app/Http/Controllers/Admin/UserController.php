<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Fetch users, ordered by ID for consistent display
        $users = User::select('id', 'username', 'email', 'admin')->orderBy('id')->get();
        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate incoming request data for creating a user
        $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'], // 'confirmed' checks for password_confirmation field
        ]);

        // Create the new user
        User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash the password before saving
        ]);

        // Redirect back to the user index with a success message
        return redirect()->route('admin.users.index')->with('success', 'User created successfully!');
    }

    /**
     * Toggle the admin status of a specific user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAdmin(User $user)
    {
        // Update the 'admin' status
        $user->update(['admin' => !$user->admin]);
        // Return a JSON response indicating success
        return response()->json(['success' => true, 'message' => 'Admin status toggled successfully!']);
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Inertia\Response
     */
    public function edit(User $user)
    {
        // Render the edit page with only necessary user data
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->only('id', 'username', 'email'),
        ]);
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        // Validate incoming request data for updating a user
        $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            // Password update is optional and handled separately if needed, not in this basic update
        ]);

        // Update the user's details
        $user->update([
            'username' => $request->username,
            'email' => $request->email,
        ]);

        // Redirect back to the user index with a success message
        return redirect()->route('admin.users.index')->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        // Delete the user
        $user->delete();
        // Redirect back to the user index with a success message
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully!');
    }
}
