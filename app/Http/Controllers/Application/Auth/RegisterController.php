<?php

namespace App\Http\Controllers\Application\Auth;

use App\Http\Controllers\Controller;
use App\Models\Application\AppUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;    
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:app_users',
            'password' => 'required|string|min:8',
        ]);

        try {
            $user = AppUsers::create([
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
            ]);

            return response()->json([
                'status' => "success",
                'message' => 'Registration successful'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Registration failed: " . $e->getMessage());

            if (isset($user)) {
                $user->delete();
            }

            return response()->json([
                'status' => "error",
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
