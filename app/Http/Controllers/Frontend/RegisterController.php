<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request)
    {
        // Walidacja danych z formularza
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Utworzenie użytkownika w bazie
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Opcjonalnie: Zalogowanie użytkownika od razu po rejestracji
        Auth::login($user);

        // Przekierowanie na panel główny po udanej rejestracji
        return redirect()->route('dashboard')->with('success', 'Konto zostało utworzone pomyślnie!');
    }
}
