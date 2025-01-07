<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\File;
use App\Models\GroupMember;

class CheckMember
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return Response
     * @throws Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if there's a file_id parameter
        if ($request->has('file_id')) {
            $fileId = $request->input('file_id');
            $file = File::find($fileId);

            if (!$file) {
                throw new Exception("File not found for ID: {$fileId}");
            }

            $groupId = $file->group_id;
            $userId = Auth::id();
            $groupMember = GroupMember::where('group_id', $groupId)->where('user_id', $userId)->first();

            if (($groupId == 1) || ($groupMember)) {
                return $next($request);
            } else {
                return response()->json(['message' => 'You are not a member of this group'], 401);
            }
        }

        // Check if there's a member_id parameter
        elseif ($request->has('member_id')) {
            $memberId = $request->input('member_id');
            $userMember = GroupMember::where('user_id', $memberId)->first();

            if ($userMember) {
                return $next($request);
            } else {
                return response()->json(['message' => 'The user is not a member of this group'], 401);
            }
        }

        // Check if there are multiple IDs
        elseif ($request->has('ids')) {
            $fileIds = $request->input('ids');

            foreach ($fileIds as $fileId) {
                $file = File::find($fileId);

                if (!$file) {
                    throw new Exception("File not found for ID: {$fileId}");
                }

                $groupId = $file->group_id;
                $userId = Auth::id();
                $groupMember = GroupMember::where('group_id', $groupId)->where('user_id', $userId)->first();

                if (($groupId == 1) || ($groupMember)) {
                    continue;
                } else {
                    return response()->json(['message' => 'You are not a member of this group'], 401);
                }
            }

            // If we've made it here without returning, all IDs were valid
            return $next($request);
        }

        // If none of the above conditions are met, allow the request
        return $next($request);
    }
}
