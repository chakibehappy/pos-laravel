<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request) {
        return Inertia::render('Users/Index', [
            'users' => User::latest()->paginate(10),
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $request->id,
            'password' => $request->id ? 'nullable|min:6' : 'required|min:6',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']); // Don't overwrite password with null on update
        }

        User::updateOrCreate(['id' => $request->id], $data);

        return back()->with('message', 'User saved successfully');
    }

    public function destroy($id) {
        User::findOrFail($id)->delete();
        return back();
    }
}