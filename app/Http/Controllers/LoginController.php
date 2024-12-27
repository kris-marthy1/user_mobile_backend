<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    
    public function Login(Request $request){

        $validated = $request->validate([
            'school_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = DB::connection('clientone') // Use the 'clientone' database connection
        ->table('user_infos')
        ->where('school_id', $validated['school_id'])
        ->first();

    // Validate the password
    // if ($user && Hash::check($validated['password'], $user->password)) {
    if ($user && $validated['password'] === $user->password){
        // Return all user data if credentials are correct
        return response()->json($user);
    }

    // Return an error if authentication fails
    return response()->json(['message' => 'Invalid credentials'], 401);
    }


    
    public function validateGoogleUser(Request $request)
{
    $validated = $request->validate([
        'email' => 'required|email',
    ]);

    $user = DB::connection('clientone') // Use the 'clientone' database connection
        ->table('user_infos')
        ->where('email', $validated['email'])
        ->first();

    if ($user) {
        return response()->json($user);
    }

    return response()->json(['message' => 'User not found'], 404);
}

public function getUserDetails($school_id)
{
    $user = DB::connection('clientone')->table('user_infos')->where('school_id', $school_id)->first();
    
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }
    
    return response()->json($user);
}
}
