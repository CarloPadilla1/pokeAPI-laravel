<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    //login
    public function login(Request $request){
        $credentials = $request->only('email', 'password');
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }
        $user = Auth::user();
        $token = $user->createToken('AppToken')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function perfil(Request $request)
    {
        return response()->json($request->user()->load('person'), 200);

    }

    public function register(Request $request)
    {
        try {
            // Validar los datos tanto de usuario como de persona
            $validator = Validator::make($request->all(), [
                'nombre'   => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|min:6',
                'address'  => 'nullable|string|max:255',
                'phone'    => 'nullable|string|max:255',
                'cedula'   => 'nullable|string',
                'sexo'     => 'required|string|in:Masculino,Femenino',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Crear el usuario
            $user = User::create([
                'name'     => $request->nombre,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Intentar crear la persona asociada
            try {
                \DB::table('person')->insert([
                    'user_id' => $user->id,
                    'address' => $request->address,
                    'phone'   => $request->phone,
                    'cedula'  => $request->cedula,
                    'sexo'    => $request->sexo,
                ]);
            } catch (\Exception $e) {
                // Si falla, continuar sin la tabla person
                \Log::warning('No se pudo insertar en tabla person: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Usuario registrado correctamente',
                'user'    => $user
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
}
