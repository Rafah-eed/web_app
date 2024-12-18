<?php

namespace App\Services;
use App\Models\EventType;
use App\Models\File;
use App\Models\FileEvent;
use App\Models\Group;
use App\Models\User;
use App\Repositories\FileRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileService
{
    private FileRepository $fileRepository;


    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    public function getCurrentUserId(): int|string|null
    {
        return Auth::id();
    }

    public function create($data): File
    {
        return $this->fileRepository->create($data);
    }

    public function update($id, $data): File
    {
        return $this->fileRepository->update($data, $id);
    }

    public function delete($id): void
    {
        $this->fileRepository->delete($id);
    }

    public function setActive($active, $id)
    {
        return $this->fileRepository->setActive($active, $id);
    }

    public function setReserved($reserved, $id)
    {
        return $this->fileRepository->setReserved($reserved, $id);
    }

    public function reserveFiles(array $fileIds): bool
    {
        return $this->fileRepository->reserveFiles($fileIds);
    }

    public function uploadFileToGroup($data): ?File
    {
        return $this->fileRepository->uploadFileToGroup($data);
    }

    public function downloadFile($validatedData): ?array
    {
        return $this->fileRepository->downloadFile($validatedData);
    }

    public function addFileEvent(mixed $file_id, $user_id, int $int): FileEvent
    {
        return $this->fileRepository->addFileEvent($file_id, $user_id, $int);
    }


}
