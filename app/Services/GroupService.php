<?php

namespace App\Services;

use App\Repositories\GroupRepository;
use Exception;


class GroupService
{
    protected GroupRepository $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    public function createGroup(array $data): \App\Models\Group
    {
        return $this->groupRepository->createGroup($data);
    }

    public function deleteGroup($data): int
    {
        return $this->groupRepository->deleteGroup($data);
    }

    public function allGroupsForUser(int|string|null $userId): \Illuminate\Http\JsonResponse
    {
        try {
            $result = $this->groupRepository->allGroupsForUser($userId);
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
            $result = $this->groupRepository->allGroupsForMemberUser($userId);
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
