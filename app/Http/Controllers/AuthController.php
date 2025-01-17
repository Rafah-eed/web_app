<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    protected UserRepository $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function register(Request $request): JsonResponse
    {
        try
        {
            $data = $request->all();

            $rules = [
                'firstName' => 'required|regex:/^[a-zA-Z0-9_]+$/|between:3,25',
                'lastName' => 'required|regex:/^[a-zA-Z0-9_]+$/|between:3,25',
                'email' => 'required|email',
                'password' => 'required|min:8',
                'role_type' => 'required|in:user,admin'
            ];
            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                return response()->json([
                    "messages" => $validation->errors()
                ], 422);
            }

            $user = $this->userRepository->register($data);
            if ($user)
            {
                $token = $user->createToken('auth_token')->plainTextToken;
                $user['token']=$token;

                return response()->json([
                    'messages'=>'User has been Created',
                    'user'=>$user,
                    'token'=>$token
                ]);
            }
            else
            {
                return response()->json([
                    'messages'=>'the process has failed!',
                ]);
            }
        }
        catch (Exception $e)
        {
            return response()->json([
                'messages'=>'the process has failed!',
                'data'=>$e
            ]);
        }
    }
   // #[Logger]
    public function login(Request $request): JsonResponse
    {
        $data = $request->all();
        $rules = [
            'email' => 'required',
            'password' => 'required|min:8'
        ];
        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            return response()->json([
                "messages" => $validation->errors()
            ], 422);
        }

        $credentials = $request->only(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'The password or the email is incorrect.',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
        $user['token']=$token;

        return response()->json([
            'message' => 'User has been logged in successfully.',
            'token' => $token,
            'user' => Auth::user()->toArray(),
        ]);
    }


    public function logout(): JsonResponse
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $token = Str::random(60); // Generate a random 60-character string

        $user = Auth::user();
        // Generate new access token
        $newAccessToken = $user->createToken('auth_token')->plainTextToken;

        // Update refresh token in database
        $user->update(['api_token' => $token]);

        return response()->json([
            'access_token' => $newAccessToken,
            'refresh_token' => $token,
            'message' => 'Access token refreshed successfully',
        ]);
    }



}
