<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function allUserFiles(): JsonResponse
    {
        $files=$this->userService->allUserFiles();
        return response()->json([
            'messages'=>'User Files',
            'data'=>$files
        ]);
    }

    public function getCurrentUserId(): bool|string
    {
        $userId = auth()->check() ? (int)auth()->id() : null;

        return json_encode($userId);
    }

}
