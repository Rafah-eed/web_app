<?php

namespace App\Repositories;

use App\Models\EventType;
use App\Models\FileEvent;
use App\Models\File;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileRepository
{
    protected File $fileModel;
    protected User $userModel;
    protected Group $groupModel;
    protected FileEvent $fileEventModel;
    protected EventType $eventTypeModel;
    private ?\Illuminate\Contracts\Auth\Authenticatable $authUser;

    public function __construct(File $fileModel, User $userModel, Group $groupModel, FileEvent $fileEventModel, EventType $eventTypeModel)
    {
        $this->fileModel = $fileModel;
        $this->userModel = $userModel;
        $this->groupModel = $groupModel;
        $this->fileEventModel = $fileEventModel;
        $this->eventTypeModel = $eventTypeModel;
    }


    public function create(array $data): ?File
    {
        return $this->fileModel->create($data);
    }

    public function update(array $data, int $id): ?File
    {
        $file = $this->fileModel->findOrFail($id);
        $file->update($data);
        return $file;
    }

    public function delete(int $id): void
    {
        $this->fileModel->destroy($id);
    }

    public function setActive(bool $isActive, int $id): ?File
    {
        return $this->fileModel->findOrFail($id)->update(['is_active' => $isActive]);
    }

    public function setReserved(bool $isReserved, int $id): ?File
    {
        return $this->fileModel->findOrFail($id)->update(['is_reserved' => $isReserved]);
    }

    public function reserveFiles(array $fileIds): bool
    {
        try {
            DB::transaction(function () use ($fileIds) {
                $this->fileModel->whereIn('file_id', $fileIds)->update([
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
        return $this->fileModel->where('group_id', $groupId)
            ->where('name', $fileName)
            ->where('is_active', true)
            ->exists();
    }

    public function uploadFileToGroup(array $data): ?File
    {
        $groupName = $this->getGroupName($data['group_id']);
        $fileName = $data['file']->getClientOriginalName();
        $basename = pathinfo($fileName, PATHINFO_FILENAME);
        $fileExtension = $data['file']->getClientOriginalExtension();

        if (!$this->checkFileIfExist($data['group_id'], $basename, $fileExtension)) {
            Storage::disk('local')->put($groupName . '/' . $fileName, file_get_contents($data['file']), [
                'overwrite' => false,
            ]);

            $fileUrl = Storage::disk('local')->url($groupName . '/' . $fileName);
            $this->fileModel->fill([
                'name' => $basename,
                'extension' => $fileExtension,
                'group_id' => $data['group_id'],
                'user_id' => auth()->id(),
                'is_active' => true,
                'is_reserved' => false,
                'path' => $fileUrl,
            ]);
            $this->fileModel->save();

            return $this->fileModel;
        }

        return null;
    }

    private function getGroupName(int $groupId): string
    {
        return $this->groupModel->find($groupId)->name ?? '';
    }

    public function downloadFile($data):?array
    {
        $fileUrl = $this->fileModel->where('id', $data['file_id'])->first()->path;
        // dd($fileUrl);
        $fileName = basename($fileUrl);
        //dd($fileName);
        $fileContent = Storage::disk('local')->get($fileUrl);
        $mimeType = Storage::disk('local')->mimeType($fileUrl);
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        $responseData = [
            'content' => $fileContent,
            'headers' => $headers,
        ];
        //dd($responseData);

        return $responseData;
    }

    public function addFileEvent(mixed $file_id, $user_id, $event_type_id): FileEvent
    {
        $fileEventModel= new FileEvent();
        $fileEventModel->file_id=$file_id;
        $fileEventModel->event_type_id=$event_type_id;
        $fileEventModel->user_id=$user_id;
        $fileEventModel->date=Carbon::now();
        $fileEventModel->save();

        return $fileEventModel;

    }
}

