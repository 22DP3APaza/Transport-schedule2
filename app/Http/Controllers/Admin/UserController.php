<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'username', 'email', 'admin')->get();
        return inertia('Admin/Users/Index', compact('users'));
    }

    public function toggleAdmin(User $user)
    {
        $user->update(['admin' => !$user->admin]);
        return response()->json(['success' => true]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['success' => true]);
    }

    public function edit(User $user)
    {
    return inertia('Admin/Users/Edit', [
        'user' => $user->only('id', 'username', 'email'),
    ]);
    }

}
