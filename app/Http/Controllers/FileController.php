<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileEvent;
use App\Models\FileUserReserved;
use App\Services\FileService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    private FileService $fileService;
    private File $fileModel;

    public function __construct( FileService $fileService, File $fileModel)
    {
        $this->fileService = $fileService;
        $this->fileModel = $fileModel;
    }

    public function uploadFileToGroup(Request $request): ?File
    {
        $validatedData = $request->validate([
            'file' => ['required', 'max:2048'],
            'group_id' => ['required', 'exists:groups,id'],
        ]);

        return $this->fileService->uploadFileToGroup($validatedData);
    }

    public function downloadFile(Request $request): JsonResponse
    {
        $data = $request->all();
        $validation = $request->validate([
            'file_id' => 'required|integer'
        ]);

        $user_id = Auth::id();
        $data['user_id'] = $user_id;

        DB::beginTransaction();

        try {
            $responseData = $this->fileService->downloadFile($data);
            $this->fileService->addFileEvent($data['file_id'], $user_id, 2);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'File downloaded successfully',
                'url' => $responseData
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error_code' => 500
            ], 500);
        }
    }

    public function deleteFile(Request $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            try {
                // Validate input
                $validatedData = $request->validate([
                    'file_id' => ['required', 'integer'],
                ]);

                // Get file data
                $fileId = $validatedData['file_id'];
                $userId = auth()->id();

                // Delete associated file events
                FileEvent::where('file_id', $fileId)->delete();

                // Delete the file
                if (Storage::delete($this->fileModel->where('id', $fileId)->where('user_id', $userId)->first()?->path)) {

                    // Remove the record from the database
                    $this->fileModel->where('id', $fileId)->delete();

                    Log::info("File deleted successfully");
                }
                    return response()->json([
                        'status' => true,
                        'message' => 'File deleted successfully',
                    ], 200);
                }
             catch (\Exception $e) {
                Log::error("Error deleting file: " . $e->getMessage());
                return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }

        });
        return response()->json(['status' => true, 'message' => "did we reach here"], 200);

    }

    public function checkIn(Request $request): JsonResponse// which is the same as reservation method
    {
        $data = $request->all();
        $rules = ['file_id' => 'required|integer'];

        try {
            $this->validate($request, $rules);

            $user_id = Auth::id();
            $checkin = $this->fileService->checkIn($data);

            if (!$checkin) {
                return response()->json(['status' => false, 'message' => 'File Not Reserved'], 500);
            }

            DB::transaction(function () use ($data, $user_id) {

                $this->fileService->addFileEvent($data['file_id'], $user_id, 4);
                $file = File::find($data['file_id']);
                $file_user_reserved = new FileUserReserved();
                $file_user_reserved->group_id = $file->group_id;
                $file_user_reserved->user_id = $user_id;
                $file_user_reserved->save();
            });

            return response()->json(['status' => true, 'message' => 'The File Has Been Reserved'], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => false, 'message' => $e->validator->errors()->first()], 400);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => 'An error occurred while processing your request'], 500);
        }
    }

    public function checkOut(Request $request):JsonResponse
    {
        $data=$request->all();

        $user_id=Auth::id();
        $checkout=$this->fileService->checkOut($data);
        DB::beginTransaction();
        try {

            if($checkout)
            {
                $this->fileService->addFileEvent($data['file_id'],$user_id,5);
                $deleteFromDatabase=$this->fileService->deleteReservationFromDatabase($data['file_id']);
            if ($deleteFromDatabase)
               return response()->json(['status'=>true,'message'=>'Success, File Has Been Un-Reserved'],200);
            }
            else
            {
                return response()->json(['status'=>false,'message'=>'Unreserving the file failed'],500);

            }
        }catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
        return response()->json(['status'=>true,'message'=>'function ended'],200);

    }

    public function updateFileInGroup(Request $request): JsonResponse
    {
        $data=$request->validate([
            'file'=>'required',
            'file_id' => ['required', 'integer', 'exists:files,id'],
        ]);

        if (!$data)
        {
            return response()->json(['status'=>false,'message'=>'errors in validating input.. try different input types'],500);
        }

        $data = $request->all();

        $file=$this->fileService->updateFileInGroup($data);
        DB::beginTransaction();
        try {
            if ($file)
            {
                $this->fileService->addFileEvent($file->id,Auth::id(),6);
                DB::commit();
                return response()->json(['status'=>true,'message'=>'File updated successfully'],200);
            }
            else
            {
                return response()->json(['status'=>false,'message'=>'File update failed'],500);
            }
        }catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @throws Exception
     */
    public function CheckInMultipleFiles(Request $request):JsonResponse
    {
        $data=$request->all();
        $result=$this->fileService->CheckInMultipleFiles($data);
        DB::beginTransaction();
        try{
            if ($result)
            {
                DB::commit();

                return response()->json(['status'=>true,'message'=>'Files Has Been Checked In'],200);
            }
            else
            {
                return response()->json(['status'=>false,'message'=>'Error in the operation'],500);
            }
        }catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    public function showReportForUser($userId)
    {
        return $this->fileService->showReportForUser($userId);
    }

    public function showReportForFile($file_id)
    {
        return $this->fileService->showReportForFile($file_id);
    }
}
