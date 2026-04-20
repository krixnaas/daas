<?php 

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthController extends Controller{

    public function register(Request $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name, 
            'email' => $request->email, 
            'password' => $request->password
        ]);

        $token = $user->createToken($request->define_name)->plainTextToken;

        return response()->json([
            'token' => $token
        ], 201);
    }

    public function login (Request $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first(); 

        if(! $user || ! Hash::check($request->password, $user->password)){
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken($request->devine_name)->plainTextToken;

        return response()->json([
            'token' => $token
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            $request->user()->only(['id', 'name', 'email'])
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}