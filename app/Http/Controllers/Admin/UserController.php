<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Gebruikers', [
            'gebruikers' => User::with('client:id,naam')->orderBy('name')->get(['id', 'name', 'email', 'role', 'client_id', 'created_at']),
            'clients'    => Client::orderBy('naam')->get(['id', 'naam']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|lowercase|email|max:255|unique:users',
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
            'role'      => ['required', Rule::in(['medewerker', 'klant'])],
            'client_id' => ['nullable', 'exists:clients,id', Rule::requiredIf($request->role === 'klant')],
        ]);

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'client_id' => $request->role === 'klant' ? $request->client_id : null,
        ]);

        return back()->with('success', 'Account aangemaakt.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->withErrors(['user' => 'Het admin-account kan niet worden bewerkt.']);
        }

        $request->validate([
            'role'      => ['required', Rule::in(['medewerker', 'klant'])],
            'client_id' => ['nullable', 'exists:clients,id', Rule::requiredIf($request->role === 'klant')],
        ]);

        $user->update([
            'role'      => $request->role,
            'client_id' => $request->role === 'klant' ? $request->client_id : null,
        ]);

        return back()->with('success', 'Account bijgewerkt.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->withErrors(['user' => 'Het admin-account kan niet worden verwijderd.']);
        }

        if ($user->id === Auth::id()) {
            return back()->withErrors(['user' => 'Je kunt je eigen account niet verwijderen.']);
        }

        $user->delete();

        return back()->with('success', 'Account verwijderd.');
    }
}
