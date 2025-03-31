<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    public function login(Request $request)
    {   
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        $user->apiTokens()->delete();

        $plainTextToken = Str::random(40);
        $token = $user->apiTokens()->create([
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => Carbon::now()->addMinutes(5)
        ]);

        return response()->json([
            'access_token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 300, 
            'renew_url' => url('api/auth/renew')
        ]);
    }

    public function renewToken(Request $request)
    {
        $user = $request->user();
        
        $user->apiTokens()->update([
            'expires_at' => Carbon::now()->addMinutes(5)
        ]);

        return response()->json([
            'message' => 'Token renovado com sucesso',
            'new_expires_at' => Carbon::now()->addMinutes(5)->toDateTimeString()
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->apiTokens()->delete();
        
        return response()->json(['message' => 'Logout realizado com sucesso']);
    }
}