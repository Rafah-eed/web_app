<?php

namespace App\Services;
use App\Models\EventType;
use App\Models\File;
use App\Models\FileEvent;
use App\Models\Group;
use App\Models\User;
use App\Repositories\FileRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileService
{
    private FileRepositoryInterface $fileRepositoryInterface;


    public function __construct(FileService $fileService, FileRepositoryInterface $fileRepositoryInterface, File $fileModel, User $userModel, Group $groupModel, EventType $eventTypeModel, FileEvent $fileEventModel)
    {
        $this->fileService = $fileService;
        $this->fileRepositoryInterface = $fileRepositoryInterface;
    }

    public function getCurrentUserId(): int|string|null
    {
        return Auth::id();
    }

    public function create($data): File
    {
        return $this->fileRepositoryInterface->create($data);
    }

    public function update($id, $data): File
    {
        return $this->fileRepositoryInterface->update($data, $id);
    }

    public function delete($id): void
    {
        $this->fileRepositoryInterface->delete($id);
    }

    public function setActive($active, $id)
    {
        return $this->fileRepositoryInterface->setActive($active, $id);
    }

    public function setReserved($reserved, $id)
    {
        return $this->fileRepositoryInterface->setReserved($reserved, $id);
    }

    public function reserveFiles(array $fileIds): bool
    {
        return $this->fileRepositoryInterface->reserveFiles($fileIds);
    }

    public function uploadFileToGroup(array $data)
    {
        return $this->fileRepositoryInterface->uploadFileToGroup($data);
    }

    public function findById($id)
    {
    }

}
