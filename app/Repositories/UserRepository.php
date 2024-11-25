<?php

namespace App\Repositories;

use App\Models\File;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;

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

    public function all()
    {
        // TODO: Implement all() method.
    }

    public function find($id)
    {
        // TODO: Implement find() method.
    }

    public function create(array $data)
    {
        // TODO: Implement create() method.
    }

    public function update(array $data, $id)
    {
        // TODO: Implement update() method.
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }
}
