<?php

namespace App\Repositories;

use App\Models\EventType;
use App\Models\FileEvent;
use App\Models\File;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileRepository implements FileRepositoryInterface
{
    protected  File $fileModel;
    protected User $userModel;
    protected Group $groupModel;
    protected  FileEvent $fileEventModel;
    protected EventType $eventTypeModel;
    private ?\Illuminate\Contracts\Auth\Authenticatable $authUser;

    public function __construct(File $model, User $userModel, Group $groupModel, FileEvent $fileEventModel, EventType $eventTypeModel)
    {
        $this->model = $model;
        $this->userModel = $userModel;
        $this->groupModel = $groupModel;
        $this->fileEventModel = $fileEventModel;
        $this->eventTypeModel = $eventTypeModel;
    }


    public function create(array $data): ?File
    {
        return $this->model->create($data);
    }

    public function update(array $data, int $id): ?File
    {
        $file = $this->model->findOrFail($id);
        $file->update($data);
        return $file;
    }

    public function delete(int $id): void
    {
        $this->model->destroy($id);
    }

    public function setActive(bool $isActive, int $id): ?File
    {
        return $this->model->findOrFail($id)->update(['is_active' => $isActive]);
    }

    public function setReserved(bool $isReserved, int $id): ?File
    {
        return $this->model->findOrFail($id)->update(['is_reserved' => $isReserved]);
    }

    public function reserveFiles(array $fileIds): bool
    {
        try {
            DB::transaction(function () use ($fileIds) {
                $this->model->whereIn('file_id', $fileIds)->update([
                    'user_id' => auth()->id(),
                    'date' => now()->toDateString(),
                    'details' => null,
                ]);
            });
            return true;
        } catch (\Exception $e) {
            Log::error('Error reserving files: ' . $e->getMessage());
            return false;
        }
    }

    public function getCurrentUserId(): ?int
    {
        return auth()->check() ? auth()->id() : null;
    }

    public function checkFileIfExist($groupId, $fileName): bool
    {
        return $this->model->where('group_id', $groupId)
            ->where('name', $fileName)
            ->where('is_active', true)
            ->exists();
    }


    public function uploadFileToGroup(array $data): ?File
    {
        $groupName = $this->groupModel->where('id', $data['group_id'])->first()->name;
        $file = $data['file'];
        $fileName = $file->getClientOriginalName();
        if (!$this->checkFileIfExist($data['group_id'],$fileName)) {
            $exist = Storage::disk('local')->exists($groupName . '/' . $fileName);
            if (!$exist) {
                Storage::disk('local')->put($groupName . '/' . $fileName, file_get_contents($file), [
                    'overwrite' => false,
                ]);
                $fileUrl = Storage::disk('local')->url($groupName . '/' . $fileName);
                $this->fileModel->name = $fileName;
                $this->fileModel->group_id = $data['group_id'];
                $this->fileModel->user_id = $data['user_id'];
                $this->fileModel->is_active = true;
                $this->fileModel->is_reserved = false;
                $this->fileModel->path = $fileUrl;
                $this->fileModel->save();
                return $this->fileModel;
            } else
                return null;

        } else {
            return null;
        }

    }
}

