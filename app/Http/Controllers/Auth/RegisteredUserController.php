<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     *
     * Only accessible after completing the onboarding setup wizard,
     * which stores a `places_id` in the session via PreviewController::complete().
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('places_id')) {
            return redirect('/');
        }

        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $request->session()->has('places_id')) {
            return redirect('/');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'places_id' => $request->session()->get('places_id')
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
