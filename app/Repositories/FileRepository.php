<?php

namespace App\Repositories;

use App\Models\EventType;
use App\Models\FileEvent;
use App\Models\File;
use App\Models\FileUserReserved;
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

    public function deleteFile($data): bool
    {
        $result = $this->fileModel->where('id', $data['file_id'])->where('user_id', $data['user_id'])->update(['is_active' => 0]);

        $file = $this->fileModel->where('id', $data['file_id'])->where('user_id', $data['user_id'])->first();

        if (!$file) {
            Log::error("File not found for ID: {$data['file_id']}");
            return false;
        }

        $path = $file->path;
        $newPath = 'trash/' . $file->name . '.' . $file->extension;

        try {
            if ($file->group_id == 1) {
                $path = 'public/' . $file->name . '.' . $file->extension;
            }

            // Check if the source file exists
            if (!File::exists($path)) {
                Log::error("Source file not found: {$path}");
                return false;
            }

            // Delete the file
            if (!Storage::delete($path)) {
                throw new \Exception("Failed to delete file: {$path}");
            }

            // Update the file path in the database
            $this->fileModel->where('id', $data['file_id'])->update(['path' => $newPath]);

            Log::info("File moved successfully: {$path} -> {$newPath}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error deleting file: " . $e->getMessage());
            return false;
        }

    }

    public function checkIn(array $data)
    {
        $result= $this->fileModel->where('id',$data['file_id'])->where('is_active',1)->lockForUpdate()->update(['is_reserved'=>1]);
        return $result;
    }

    public function checkOut($data): bool
    {
        $result= $this->fileModel->where('id',$data['file_id'])->where('is_active',1)->update(['is_reserved'=>0]);
        return $result;
    }

    public function deleteReservationFromDatabase($file_id): bool
    {
            $file=File::find($file_id);
            FileUserReserved::where('group_id', $file->group_id)->where('user_id', $file->user_id)->delete();
            DB::commit();
            return true;
    }

    public function updateFileInGroup($data)
    {
        $fileId = $data['file_id'];
        $existingFile = $this->fileModel->where('id', $fileId)->get()->first();

        if (!$existingFile) {
            return null;
        }

        $newFileName = $data['file']->getClientOriginalName();
        $basename = pathinfo($newFileName, PATHINFO_FILENAME);
        $fileExtension = $data['file']->getClientOriginalExtension();

        $updatedFileDb = $this->fileModel->where('id', $existingFile->id)
            ->where('name', $basename)
            ->where('extension', $fileExtension)
            ->get();


        if (!$updatedFileDb) {
            return null;
        }
        $group_id=$existingFile["group_id"];

        $group = $this->groupModel->where('id', $group_id)->get()->first();
        $groupName = $group->name;
        $exist = Storage::disk('local')->exists($groupName . '/' . $newFileName);

        if ($exist) {
            $result = Storage::disk('local')->put($groupName . '/' . $newFileName, file_get_contents($data['file']), [
                'overwrite' => true,
            ]);

            if ($result) {
                $maxVersion = DB::table('files')->max('version') ?: 1;

                // Increment the version
                $newVersion = $maxVersion + 1;

                // Update the record with the new version
                DB::table('files')->where('id', $existingFile->id)->update(['version' => $newVersion]);

                return $existingFile;
            }
        } else {
            return null;
        }
        return null;
    }

    public function CheckInMultipleFiles(array $data): bool
    {
        $isReserved=false;

        foreach ($data['ids'] as $fileId) {
            $file = File::find($fileId);

            if (!$file) {
                throw new \Exception("File not found for ID: {$fileId}");
            }

            $result= $this->fileModel->where('id',$fileId)->where('is_active',1)->lockForUpdate()->update(['is_reserved'=>1]);

            if ($result) {
                $isReserved = true;
                $file_user_reserved = new FileUserReserved();
                $file_user_reserved->group_id = $file->group_id;
                $file_user_reserved->user_id = $file->user_id;
                $file_user_reserved->save();
            }
            else
                $isReserved=false;

        }
        return $isReserved;
    }
    public function showReport()
    {
        $fileEvents = FileEvent::with('file','user','eventType')->get();
        return response()->json(['file_events' => $fileEvents], 200);
    }


}

