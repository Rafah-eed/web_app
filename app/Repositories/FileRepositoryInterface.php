<?php

namespace App\Repositories;

use App\Models\File;

interface FileRepositoryInterface
{
    public function create(array $data);
    public function update(array $data, int $id);
    public function delete(int $id);
    public function setActive(bool $isIsActive, int $id);
    public function setReserved(bool $isIsReserved, int $id);
    public function reserveFiles(array $fileIds): bool;
    public function getCurrentUserId(): ?int;
    public function checkFileIfExist($groupId, $fileName): bool;
    public function uploadFileToGroup(array $data);
}

