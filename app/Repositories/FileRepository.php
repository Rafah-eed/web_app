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
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


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

        if (!$this->checkFileIfExist($data['group_id'], $basename )) {

            $version = 1;

            while ($this->fileModel->where('group_id', $data['group_id'])->where('name', $basename . '.' . $fileExtension)->exists()) {
                $existingFiles = $this->fileModel->where('group_id', $data['group_id'])
                    ->where('name', $basename . '.' . $fileExtension)
                    ->orderBy('version', 'desc')
                    ->get();

                if (!empty($existingFiles) && $existingFiles->count() > 0) {
                    $maxVersion = $existingFiles->max('version');
                    $version = $maxVersion + 1;
                } else {
                    break;
                }
            }

            $newFileName = $basename . '.' . $fileExtension;


            Storage::disk('local')->put($groupName . '/' . $newFileName, file_get_contents($data['file']), [
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
                'version' => $version,
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

//    public function downloadFile($data): string
//    {
//        // Get the file path from the database
//        $file = $this->fileModel->where('id', $data['file_id'])->first();
//
//        if (!$file || !$file->path) {
//            abort(404, 'File not found');
//        }
//        return Response::download($file);
//        // Generate the URL for the file
//        //return $file->path;
//    }

    public function downloadFile($file_id): BinaryFileResponse
    {
        Log::info('DownloadFile method called with file_id: ' . $file_id);

        // Get the file path from the database
        $file = $this->fileModel->where('id', $file_id)->first();

        if (!$file || !$file->path) {
            abort(404, 'File not found');
        }

        $filePath = storage_path($file->path);

        try {
            // Generate the response
            return Response::download($filePath, $file->name, [
                'content_type' => $file->extension,
                'expires' => 0,
            ]);
        } catch (\Exception $e) {
            Log::error("Error downloading file: " . $e->getMessage());
            abort(500, 'An error occurred while processing your request.');
        }
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
        $file = $this->fileModel->where('id', $data['file_id'])
            ->where('user_id', auth::id())
            ->first();

        if (!$file) {
            Log::error("File not found for ID: {$data['file_id']}");
            return false;
        }

        $path = $file->path;

        try {
            // Delete associated file events
            FileEvent::where('file_id', $data['file_id'])->delete();

            // Delete the file
            if (!Storage::delete($path)) {
                throw new \Exception("Failed to delete file: {$path}");
            }

            // Remove the record from the database
            $this->fileModel->where('id', $data['file_id'])->delete();

            Log::info("File deleted successfully: {$path}");
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

        $group_id = $existingFile["group_id"];
        $group = $this->groupModel->where('id', $group_id)->get()->first();
        $groupName = $group->name;

        // Construct the new path using public disk
        $newPath = Storage::disk('public')->putFileAs($groupName, $data['file'], $newFileName);

        // Check if the file already exists in the new location
        $exist = Storage::disk('public')->exists($newPath);

        if ($exist) {
            // If it exists, update the existing file
            $result = Storage::disk('public')->put($newPath, file_get_contents($data['file']), [
                'overwrite' => true,
            ]);

            if ($result) {
                $maxVersion = DB::table('files')->max('version') ?: 1;

                // Increment the version
                $newVersion = $maxVersion + 1;


                $newFileNameWithVersion = $basename  . '.' .$newVersion ;
                $updatedFileDb = $this->fileModel->where('id', $existingFile->id)
                    ->where('name', $newFileNameWithVersion)
                    ->where('extension', $fileExtension)
                    ->get();

                if (!$updatedFileDb) {
                    return null;
                }
                // Update record with new filename and path
                DB::table('files')->where('id', $existingFile->id)->update([
                    'version' => $newVersion,
                    'path' => Storage::disk('local')->url($newPath)
                ]);

                return $existingFile;
            }
        } else {
            // If it doesn't exist, store the file in the new location
            $result = Storage::disk('public')->put($newPath, file_get_contents($data['file']), [
                'overwrite' => true,
            ]);

            if ($result) {
                $maxVersion = DB::table('files')->max('version') ?: 1;

                // Increment the version
                $newVersion = $maxVersion + 1;

                // Update the record with the new version and path
                DB::table('files')->where('id', $existingFile->id)->update([
                    'version' => $newVersion,
                    'path' => $newPath
                ]);

                return $existingFile;
            }
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

    public function showReportForUser($userId)
    {
        return FileEvent::where('user_id',$userId)->with('file','user','eventType')->get();

    }

    public function showReportForFile($fileId)
    {
        return FileEvent::where('file_id',$fileId)->with('file','user','eventType')->get();
    }


}

