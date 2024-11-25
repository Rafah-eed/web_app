<?php

namespace App\Http\Controllers;

use App\Services\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    protected GroupService $groupService;

    public function __construct(GroupService $groupService)
    {

        $this->groupService = $groupService;
    }

    public function createGroup(Request $request): JsonResponse
    {
        $data = $request->all();
        $rules = [
            'name' => 'required',
            'owner_id' => 'required|exists:users,id'
        ];

        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
           return response()->json([
                "messages" => $validation->errors()
            ], 422);
        }
        $group = $this->groupService->createGroup($data);

            return response()->json([
                'messages'=>'Group Created Successfully',
                'data'=>$group
            ],201);

    }

    public function deleteGroup(Request $request): JsonResponse
    {
        $data=$request->all();
        if($this->groupService->deleteGroup($data)) {
            return response()->json([
                'messages'=>'Group Deleted Successfully',
                'data'=>$data
            ],204);
        }
        else {
            return response()->json([
                'messages'=>'Not Owned Group',
            ],401);
        }
    }

    public function allGroupsForUser(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->groupService->allGroupsForUser($userId);

        if ($result && !empty($result)) {
            return response()->json([
                'messages' => 'Groups fetched Successfully',
                'data' => $result
            ], 200);
        } else {
            return response()->json([
                'message' => 'No groups found for this user',
            ], 404);
        }
    }

    public function allGroupsForMemberUser(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->groupService->allGroupsForMemberUser($userId);

        if ($result && !empty($result)) {
            return response()->json([
                'messages' => 'Groups fetched Successfully',
                'data' => $result
            ], 200);
        } else {
            return response()->json([
                'message' => 'No groups found for this user',
            ], 404);
        }
    }
}
