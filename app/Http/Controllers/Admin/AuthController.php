<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Mostrar formulario de login
     */
    public function showLogin()
    {
        if (Auth::guard('administrador')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Admin/Auth/Login');
    }

    /**
     * Procesar login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'El correu electrònic és obligatori',
            'email.email' => 'El correu electrònic no és vàlid',
            'password.required' => 'La contrasenya és obligatòria',
        ]);

        $administrador = Administrador::where('email', $credentials['email'])->first();

        if (!$administrador) {
            return back()->withErrors([
                'email' => 'No tens permís per accedir a l\'administració.',
            ]);
        }

        if (!$administrador->activo) {
            return back()->withErrors([
                'email' => 'El teu compte està desactivat.',
            ]);
        }

        if (Auth::guard('administrador')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Actualizar último acceso
            $administrador->actualizarUltimoAcceso();
            
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Les credencials no són correctes.',
        ]);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        Auth::guard('administrador')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Listar usuarios administradores
     */
    public function index()
    {
        $administradores = Administrador::orderBy('nombre')->get();

        return Inertia::render('Admin/Usuarios/Index', [
            'administradores' => $administradores,
        ]);
    }

    /**
     * Crear nuevo usuario administrador
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:administradores,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'tipo' => ['required', Rule::in(['super_admin', 'admin', 'editor'])],
        ], [
            'nombre.required' => 'El nom és obligatori',
            'email.required' => 'El correu electrònic és obligatori',
            'email.email' => 'El correu electrònic no és vàlid',
            'email.unique' => 'Aquest correu ja està registrat',
            'password.required' => 'La contrasenya és obligatòria',
            'password.confirmed' => 'Les contrasenyes no coincideixen',
            'password.min' => 'La contrasenya ha de tenir mínim 8 caràcters',
            'tipo.required' => 'El tipus d\'administrador és obligatori',
            'tipo.in' => 'El tipus d\'administrador no és vàlid',
        ]);

        Administrador::create([
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'tipo' => $validated['tipo'],
            'activo' => true,
        ]);

        return back()->with('success', 'Administrador creat correctament');
    }

    /**
     * Actualizar usuario administrador
     */
    public function update(Request $request, Administrador $usuario)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:administradores,email,' . $usuario->id],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'tipo' => ['required', Rule::in(['super_admin', 'admin', 'editor'])],
            'activo' => ['boolean'],
        ], [
            'nombre.required' => 'El nom és obligatori',
            'email.required' => 'El correu electrònic és obligatori',
            'email.email' => 'El correu electrònic no és vàlid',
            'email.unique' => 'Aquest correu ja està registrat',
            'password.confirmed' => 'Les contrasenyes no coincideixen',
            'password.min' => 'La contrasenya ha de tenir mínim 8 caràcters',
            'tipo.required' => 'El tipus d\'administrador és obligatori',
            'tipo.in' => 'El tipus d\'administrador no és vàlid',
        ]);

        $usuario->nombre = $validated['nombre'];
        $usuario->email = $validated['email'];
        $usuario->tipo = $validated['tipo'];
        
        if (!empty($validated['password'])) {
            $usuario->password = Hash::make($validated['password']);
        }
        
        if (isset($validated['activo'])) {
            // No permitir desactivarse a uno mismo
            if ($usuario->id === Auth::guard('administrador')->id() && !$validated['activo']) {
                return back()->withErrors(['activo' => 'No pots desactivar el teu propi compte']);
            }
            $usuario->activo = $validated['activo'];
        }

        $usuario->save();

        return back()->with('success', 'Administrador actualitzat correctament');
    }

    /**
     * Eliminar usuario administrador
     */
    public function destroy(Administrador $usuario)
    {
        // No permitir eliminarse a uno mismo
        if ($usuario->id === Auth::guard('administrador')->id()) {
            return back()->withErrors(['error' => 'No pots eliminar el teu propi compte']);
        }

        // Asegurarse de que queda al menos un admin activo
        $adminCount = Administrador::where('activo', true)->count();
        if ($adminCount <= 1 && $usuario->activo) {
            return back()->withErrors(['error' => 'Ha d\'haver almenys un administrador actiu']);
        }

        $usuario->delete();

        return back()->with('success', 'Administrador eliminat correctament');
    }
}
