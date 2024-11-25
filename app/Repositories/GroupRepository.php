<?php

namespace App\Repositories;

use App\Models\Group;
use App\Models\GroupMember;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class GroupRepository
{

    public function createGroup(array $data): Group
    {

        // Create and save the group
        $group = new Group();
        $group->name = $data['name'];
        $group->owner_id = $data['owner_id'];
        $group->save();

// Create and save the group member
        $groupMember = new GroupMember();
        $groupMember->group_id = $group->id;
        $groupMember->user_id = $data['owner_id'];
        $groupMember->join_date = Carbon::now();
        $groupMember->save();

// Prepare the return group
        $returnGroup = new Group();
        $returnGroup->id = $group->id;
        $returnGroup->name = $group->name;
        $returnGroup->owner_id = $group->owner_id;
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

}
