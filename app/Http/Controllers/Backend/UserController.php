<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role ?? 'none';
        
        if (!in_array($role, ['admin', 'mod'])) {
            abort(403, 'Brak dostępu.');
        }

        $users = User::all();
        
        return view("Backend.{$role}.users.index", compact('users'));
    }

    public function create()
    {
        $role = auth()->user()->role ?? 'none';

        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        return view('Backend.admin.users.create');
    }

    public function store(Request $request)
    {
        $role = auth()->user()->role ?? 'none';

        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:admin,mod,user,none',
        ], [
            'name.required'      => 'Imię i nazwisko jest wymagane.',
            'email.required'     => 'Adres e-mail jest wymagany.',
            'email.email'        => 'Podaj prawidłowy adres e-mail.',
            'email.unique'       => 'Użytkownik z tym adresem e-mail już istnieje.',
            'password.required'  => 'Hasło jest wymagane.',
            'password.min'       => 'Hasło musi mieć co najmniej 8 znaków.',
            'password.confirmed' => 'Potwierdzenie hasła nie zgadza się.',
            'role.required'      => 'Rola jest wymagana.',
            'role.in'            => 'Wybierz prawidłową rolę.',
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Użytkownik został pomyślnie utworzony.');
    }

    public function edit(User $user)
    {
        $role = auth()->user()->role ?? 'none';

        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        return view('Backend.admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $role = auth()->user()->role ?? 'none';

        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'role'     => 'required|in:admin,mod,user,none',
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'name.required'      => 'Imię i nazwisko jest wymagane.',
            'email.required'     => 'Adres e-mail jest wymagany.',
            'email.email'        => 'Podaj prawidłowy adres e-mail.',
            'email.unique'       => 'Użytkownik z tym adresem e-mail już istnieje.',
            'password.min'       => 'Hasło musi mieć co najmniej 8 znaków.',
            'password.confirmed' => 'Potwierdzenie hasła nie zgadza się.',
            'role.required'      => 'Rola jest wymagana.',
            'role.in'            => 'Wybierz prawidłową rolę.',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->role  = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')
            ->with('success', 'Dane użytkownika zostały zaktualizowane.');
    }
}
