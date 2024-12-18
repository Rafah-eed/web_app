<?php

namespace App\Http\Middleware;

use Closure;
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
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hasParameterFileId=$request->has('file_id');
        if ($hasParameterFileId)
        {
            $group_id = File::where('id',$request->file_id)->first()->group_id;
            $user_id = Auth::id();
            $group_member=GroupMember::where('group_id',$group_id)->where('user_id',$user_id)->first();
            if (($group_id==1)||($group_member))
            {
                return $next($request);
            }
            else
            {
                return response()->json(['message'=>'You are not a member of this group'],401);
            }

        }
        $hasParameterMemberId=$request->has('member_id');
        $memberId=$request->member_id;
        if ($hasParameterMemberId)
        {
            $userMember=GroupMember::where('user_id',$memberId)->first();
            if ($userMember)
            {
                return $next($request);
            }
            else
            {
                return response()->json(['message'=>'the User is not a member of this group'],401);
            }
        }
        $hasParametersIds=$request->all();
        if($hasParametersIds)
        {
            $count=count($hasParametersIds);
            $isMember=false;
            for($i=1;$i<=$count;$i++)
            {
                $id=$hasParametersIds['id'.$i];
                //dd($id);
                $group_id = File::where('id',$id)->first()->group_id;
                // dd($group_id);
                $user_id = Auth::id() ;
                $group_member = GroupMember::where('group_id',$group_id)->where('user_id',$user_id)->first();
                if (($group_id==1)||($group_member))
                {
                    $isMember=true;
                }
                else
                {
                    $isMember=false;
                }
            }
            if ($isMember)
                return $next($request);
            else
            {
                return response()->json(['message'=>'You are not a member of this group'],401);
            }
        }


        return $next($request);
    }
}
