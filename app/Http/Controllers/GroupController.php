<?php

namespace App\Http\Controllers;

use App\Repositories\GroupRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    protected GroupRepository $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    public function createGroup(Request $request): \Illuminate\Http\JsonResponse
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
        $group = $this->groupRepository->createGroup($data);

            return response()->json([
                'messages'=>'Group Created Successfully',
                'data'=>$group
            ],201);

    }

    public function deleteGroup(Request $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validate([
            'group_id' => 'required|exists:groups,id'
        ]);

        if ($this->groupRepository->deleteGroup($validatedData)) {
            return response()->json([
                'message' => 'Group deleted successfully',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Not owned group',
            ], 401);
        }
    }
}
