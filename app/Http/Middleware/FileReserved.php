<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\File;

class FileReserved
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if there's a group_id parameter
        if ($request->has('group_id')) {
            $groupId = $request->input('group_id');
            $file = File::where('group_id', $groupId)->where('is_reserved', 1)->first();

            if ($file) {
                return response()->json(['message' => 'File is already reserved'], 403);
            }
        }

        // Check if there's a file_id parameter
        elseif ($request->has('file_id')) {
            $fileId = $request->input('file_id');
            $file = File::find($fileId);

            if (!$file || $file->is_reserved == 1) {
                return response()->json(['message' => 'File is already reserved'], 403);
            }
        }

        // Check multiple IDs
        elseif ($request->has('ids')) {
            $fileIds = $request->input('ids');

            foreach ($fileIds as $fileId) {
                $file = File::find($fileId);

                if (!$file || $file->is_reserved == 1) {
                    return response()->json(['message' => 'One or more files are already reserved'], 403);
                }
            }
        }

        // If none of the above conditions are met, allow the request
        return $next($request);
    }
}
