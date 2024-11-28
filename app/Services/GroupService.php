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

    public function allGroupFiles(array $data): \Illuminate\Http\JsonResponse
    {
        return $this->groupRepository->allGroupFiles($data);
    }

    public function RequestToJoinGroup(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->groupRepository->RequestToJoinGroup($request);
    }

    public function AcceptedRequest(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->groupRepository->AcceptedRequest($request);
    }

    public function refuseRequest(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->groupRepository->refuseRequest($request);
    }

    public function allSentRequestsFromGroupAdmin(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->groupRepository->allSentRequestsFromGroupAdmin($request);
    }

    public function GroupUsers(array $data)
    {
        return $this->groupRepository->GroupUsers($data);
    }

    public function displayAllUser(): \Illuminate\Http\JsonResponse
    {
        return $this->groupRepository->displayAllUser();
    }

    public function displayAllGroups(): \Illuminate\Http\JsonResponse
    {
        return $this->groupRepository->displayAllGroups();
    }

    public function searchUser(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->groupRepository->searchUser($request);
    }

}
