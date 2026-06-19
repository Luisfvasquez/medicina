<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PatientRegisterRequest;
use App\Models\PatientAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class PatientAuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // En Laravel 11 los middlewares se pueden definir en las rutas, pero también se pueden usar de la forma tradicional o mediante el método middleware de la clase Route.
    }

    /**
     * Register a Patient
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(PatientRegisterRequest $request): JsonResponse
    {
        $patient = PatientAccount::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'national_id' => $request->national_id,
            'username' => $request->username,
            'city_id' => $request->city_id,
            'password_hash' => $request->password ? Hash::make($request->password) : null,
        ]);

        $token = auth('patient_api')->login($patient);

        return $this->respondWithToken($token);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = request(['email', 'password']);
        
        // El framework internamente validará password_hash debido a que renombramos la columna en AuthPasswordName o debemos pasar el array con password_hash si el framework no lo mapea.
        // Como 'password_hash' es la columna, es mejor asegurarnos de usar Auth::guard('patient_api')->attempt() si renombramos getAuthPasswordName().
        // JWT Auth y EloquentUserProvider intentarán encontrar la password usando getAuthPassword().

        if (! $token = auth('patient_api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json(auth('patient_api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth('patient_api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('patient_api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('patient_api')->factory()->getTTL() * 60
        ]);
    }
}
