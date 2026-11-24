<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function Login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $data['username'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            return response()->json([ 'message' => 'username atau password salah' ], 422);
        }

        return response()->json([
            'message' => $user->createToken('app')->plainTextToken,
            'user' => $user->only('id', 'name', 'username', 'role'),
        ]);
    }

        public function me(Request $request)
        {
            return $request()->user()->only()('id', 'name', 'username', 'role');
        }

        public function logout(Request $request)
        {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout berhasil',
            ]);
        }
    }

