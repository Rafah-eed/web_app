<?php

namespace App\Repositories;

use App\Models\Group;
use App\Models\GroupMember;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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

    public function deleteGroup(array $data): bool
    {
        try {
            $groupOwner = Group::where('id', $data['group_id'])
                ->where('owner_id', Auth::id())
                ->first();

            if ($groupOwner) {
                $groupOwner->delete();
                return true;
            }
        } catch (\Exception $e) {
            logger()->error('Error in deleteGroup method: ' . $e->getMessage());
        }
        return false;
    }
}
