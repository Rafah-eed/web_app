<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;


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
                'email' => 'required|email|unique:users',
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
                    'data'=>$user,
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
            'email' => 'required|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/|exists:users,email',
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
                'message' => 'The password is incorrect.',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User has been logged in successfully.',
            'token' => $token,
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


    /**
     * @throws Exception
     */
    public function refresh(): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('Attempting to refresh token');

        $user = Auth::user();
        \Illuminate\Support\Facades\Log::info('User retrieved: ', [$user]);

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Log the current token
        $currentToken = request()->header('Authorization') ?? '';
        \Illuminate\Support\Facades\Log::info('Current Authorization header: ', ['token' => $currentToken]);

        // Generate new token
        $newToken = Hash::make(random_int(0, PHP_INT_MAX));

        // Store the new token securely
        session(['token' => $newToken]);

        \Illuminate\Support\Facades\Log::info('New token generated: ', ['token' => $newToken]);

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'authorization' => [
                'token' => $newToken,
                'type' => 'bearer',
            ]
        ]);
    }


}
