<?php

namespace App\Repositories;

use App\Models\Group;

interface GroupRepositoryInterface
{
    public function createGroup(array $data):Group;
    public function deleteGroup(array $data);
}
