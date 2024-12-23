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
            'name' => 'required'
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
        if($this->groupService->deleteGroup($data) > 0) {
            return response()->json([
                'messages'=>'Group Deleted Successfully',
                'data'=>$data
            ],204);
        }
        else {
            return response()->json([
                'messages'=>'You are not authenticated to delete it',
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

    public function allGroupFiles(Request $request):JsonResponse
    {
        $data=$request->all();
        $groupFiles=$this->groupService->allGroupFiles($data);
        return response()->json([
            'messages'=>'Successfully',
            'data'=>$groupFiles
        ],200);
    }

    public function RequestToJoinGroup(Request $request):JsonResponse
    {
        return $this->groupService->RequestToJoinGroup($request);
    }

    public function AcceptedRequest(Request $request):JsonResponse
    {
        return $this->groupService->AcceptedRequest($request);
    }

    public function refuseRequest(Request $request):JsonResponse
    {
        return $this->groupService->refuseRequest($request);
    }

    public function allSentRequestsFromGroupAdmin(Request $request):JsonResponse
    {
        return $this->groupService->allSentRequestsFromGroupAdmin($request);
    }

    public function allReceivedRequests(Request $request):JsonResponse
    {
        return $this->groupService->allReceivedRequests($request);
    }
    public function groupUsers(Request $request):JsonResponse
    {
        $data=$request->all();
        return response()->json([
            'messages'=>'Successfully',
            'data'=>$this->groupService->GroupUsers($data)
        ]);
    }

    public function displayAllUser(): JsonResponse
    {
        return $this->groupService->displayAllUser();
    }
    public function displayAllGroups(): JsonResponse
    {
        return $this->groupService->displayAllGroups();
    }

    public function searchUser(Request $request):JsonResponse
    {
        return $this->groupService->searchUser($request);
    }
}
