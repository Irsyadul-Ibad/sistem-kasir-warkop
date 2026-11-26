<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function exchange(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:64']]);
        $userId = Cache::pull('crew-login:'.hash('sha256', $data['code']));
        $user = $userId ? User::find($userId) : null;
        if (! $user || ! $user->is_active) {
            return response()->json(['message' => 'Kode login tidak berlaku. Silakan masuk kembali.'], 422);
        }

        return response()->json([
            'token' => $user->createToken('app')->plainTextToken,
            'user' => $user->only('id', 'name', 'username', 'role'),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Username atau password salah.'], 422);
        }

        return response()->json([
            'token' => $user->createToken('app')->plainTextToken,
            'user' => $user->only('id', 'name', 'username', 'role'),
        ]);
    }

    public function me(Request $request)
    {
        return $request->user()->only('id', 'name', 'username', 'role');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
