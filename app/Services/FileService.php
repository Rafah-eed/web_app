<?php

namespace App\Services;
use App\Models\EventType;
use App\Models\File;
use App\Models\FileEvent;
use App\Models\Group;
use App\Models\User;
use App\Repositories\FileRepository;
use Exception;
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

//    public function reserveFiles(array $fileIds): bool
//    {
//        return $this->fileRepository->reserveFiles($fileIds);
//    }

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

    public function deleteFile(array $data): bool
    {
        return $this->fileRepository->deleteFile($data);
    }

    public function checkIn(array $data)
    {
        return $this->fileRepository->checkIn($data);
    }

    public function checkOut(array $data)
    {
        return $this->fileRepository->checkIn($data);
    }

    public function updateFileInGroup($data)
    {
        return $this->fileRepository->updateFileInGroup($data);
    }

    public function deleteReservationFromDatabase($file_id): bool
    {
        return $this->fileRepository->deleteReservationFromDatabase($file_id);
    }

    /**
     * @throws Exception
     */
    public function CheckInMultipleFiles(array $data): bool
    {
        return $this->fileRepository->CheckInMultipleFiles($data);
    }


}
