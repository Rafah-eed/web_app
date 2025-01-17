<?php

namespace App\Repositories;

use App\Models\File;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    protected User $userModel;
    protected File $fileModel;
    protected Group $groupModel;
    protected  GroupMember $groupMembers;

    public function __construct(User $userModel,File $fileModel,Group $groupModel,GroupMember $groupMembers)
    {
        $this->userModel = $userModel;
        $this->fileModel = $fileModel;
        $this->groupModel = $groupModel;
        $this->groupMembers = $groupMembers;

    }

    public function register(array $data): User
    {
        $user = new User();
        $user->firstName=$data['firstName'];
        $user->lastName=$data['lastName'];
        $user->email=$data['email'];
        $user->password=bcrypt($data['password']);
        $user->role_type=$data['role_type'];
        $user->save();
        return $user;
    }



    public function allUserFiles()
    {
        $userId=Auth::id();
        return $this->fileModel
            ->where('user_id',$userId)
            ->with('group','user')
            ->get();
    }
    
}
