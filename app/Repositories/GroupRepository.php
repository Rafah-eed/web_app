<?php

namespace App\Repositories;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\RequestUserToGroups;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class GroupRepository
{
    public function createGroup(array $data): Group
    {

        // Create and save the group
        $group = new Group();
        $group->name = $data['name'];
        $group->owner_id = auth()->id();
        $group->save();

// Create and save the group member
        $groupMember = new GroupMember();
        $groupMember->group_id = $group->id;
        $groupMember->user_id = auth()->id();
        $groupMember->join_date = Carbon::now();
        $groupMember->save();

// Prepare the return group
        $returnGroup = new Group();
        $returnGroup->id = $group->id;
        $returnGroup->name = $group->name;
        $returnGroup->owner_id = auth()->id();
        $returnGroup->updated_at = $group->updated_at;
        $returnGroup->created_at = $group->created_at;

        return $returnGroup;
    }

    public function deleteGroup(array $data): int
    {
        try {
            return DB::table('groups')
                ->where('id', $data['group_id'])
                ->where('owner_id', $data['user_id'])
                ->delete();
        } catch (Exception $e) {
            logger()->error('Error in deleteGroup method: ' . $e->getMessage());
            return 0;
        }
}

    public function allGroupsForUser(int|string|null $userId): \Illuminate\Http\JsonResponse
    {
        try {
            $result = DB::table('groups')
                ->where('owner_id', $userId)
                ->get();
            return response()->json([
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            logger()->error('Error in fetching method: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error in fetching groups from repository',
            ], 500);
        }
    }

    public function allGroupsForMemberUser(int|string|null $userId): \Illuminate\Http\JsonResponse
    {
        try {
            $result = DB::table('group_members')
                ->where('user_id', $userId)
                ->get();
            return response()->json([
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            logger()->error('Error in fetching method: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error in fetching groups from repository',
            ], 500);
        }
    }

    public function allGroupFiles(array $data): \Illuminate\Http\JsonResponse
    {
        $group_id=$data['group_id'];
        try {
            $result = DB::table('files')
                ->where('group_id', $group_id)
                ->get();
            return response()->json([
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            logger()->error('Error in fetching method: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error in fetching files for the selected group',
            ], 500);
        }
    }

    public function RequestToJoinGroup(Request $request): \Illuminate\Http\JsonResponse
    {
        $data=$request->all();
        $rules=[
            'group_id'=>'required|integer',
            'user_id'=>'required|integer',
        ];
        $validation = Validator::make($data, $rules);
        if ($validation->fails())
        {
            return response()->json(['status'=>false,'message'=>$validation->errors()->first()],500);
        }
        $userRequest=RequestUserToGroups::where('group_id',$data['group_id'])->where('user_id',$data['user_id'])->first();
        if ($userRequest){
            return response()->json([
                'status'=>false,
                'message'=>'you already have sent for this user in group'
            ],405);
        }
        $existingMember = GroupMember::where('group_id', $data['group_id'])->where('user_id',$data['user_id'])->first();

        if ($existingMember) {
            return response()->json([
                'messages'=>'User in Group',
            ],405);
        }

        $newRequestToJoinGroup=new RequestUserToGroups();
        $newRequestToJoinGroup->group_id=$data['group_id'];
        $newRequestToJoinGroup->user_id=$data['user_id'];
        $newRequestToJoinGroup->save();

        return response()->json(['message'=>"request sent"]);
    }

    public function AcceptedRequest(Request $request): \Illuminate\Http\JsonResponse
    {
        $data=$request->all();
        $rules=[
            'group_id'=>'required|integer',
            'user_id'=>'required|integer'
        ];
        $validation = Validator::make($data, $rules);
        if ($validation->fails())
        {
            return response()->json(['status'=>false,'message'=>$validation->errors()->first()],500);
        }
        $userRequest=RequestUserToGroups::where('group_id',$data['group_id'])
            ->where('user_id',$data['user_id'])
            ->first();
        if (!$userRequest){
            return response()->json(['status'=>false,'message'=>'you dont have request for this user in group'],500);
        }
        $userRequest->update(['is_accepted'=>1]);

        $newGroupMember = new GroupMember();
        $newGroupMember->group_id = $data['group_id'];
        $newGroupMember->user_id = $data['user_id'];
        $newGroupMember->join_date = Carbon::now();
        $newGroupMember->save();

        $userRequest->delete();

        return response()->json(['message'=>"new member join group"]);

    }

    public function refuseRequest(Request $request): \Illuminate\Http\JsonResponse
    {
        $data=$request->all();
        $rules=[
            'group_id'=>'required|integer',
            'user_id'=>'required|integer'
        ];
        $validation = Validator::make($data, $rules);
        if ($validation->fails())
        {
            return response()->json(['status'=>false,'message'=>$validation->errors()->first()],500);
        }
        $userRequest=RequestUserToGroups::where('group_id',$data['group_id'])
            ->where('user_id',$data['user_id'])
            ->first();
        if (!$userRequest){
            return response()->json(['status'=>false,'message'=>'you dont have request for this user in group'],500);
        }
        $userRequest->update(['is_accepted'=>0]);
        $userRequest->delete();

        return response()->json(['message'=>"access denied"]);
    }

    public function allSentRequestsFromGroupAdmin($data): \Illuminate\Http\JsonResponse
    {
        $currentUserId = Auth::id();
        $group = Group::find($data->group_id);
        if (!$group) {
            return response()->json([
                'messages'=>'Group not found',
            ]);
        }
        if ($group->owner_id !== $currentUserId) {
            return response()->json([
                'messages'=>'You are not authorized to send a request',
            ]);
        }
        $allGroupRequest = RequestUserToGroups::where('group_id', $data->group_id)->with('user')->get();

        if ($allGroupRequest){
            return response()->json([
                'data'=>$allGroupRequest
            ]);
        }
        return response()->json([
            'data'=> 'you did not send any request yet'
        ],200);
    }

    public function GroupUsers(array $data)
    {
        $rules=[
            'group_id'=>'required|integer',
        ];

        $validation = Validator::make($data, $rules);

        if ($validation->fails())
        {
            return response()->json(['status'=>false,'message'=>$validation->errors()->first()],500);
        }
        return GroupMember::where('group_id',$data['group_id'])->with('user')->get();
    }

    public function displayAllUser(): \Illuminate\Http\JsonResponse
    {
        $allUser = User::all();
        return response()->json([
            'data'=>$allUser
        ]);
    }

    public function displayAllGroups(): \Illuminate\Http\JsonResponse
    {
        $allGroup = Group::all();
        return response()->json([
            'data'=>$allGroup
        ]);
    }

    public function searchUser(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->get('name');

        $user = User::where(function ($query) use ($search) {
            $query->where('firstName', 'LIKE', '%' . $search . '%')
                ->orWhere('lastName', 'LIKE', '%' . $search . '%');
        })->get();


        if ($user->isEmpty()) {
            return response()->json(['message' => 'No user found with this name'], 404);
        }

        return response()->json([
            'data' => $user
        ]);
    }


}
