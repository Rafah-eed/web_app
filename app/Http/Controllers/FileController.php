<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileUserReserved;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    private FileService $fileService;

    public function __construct( FileService $fileService)
    {
        $this->fileService = $fileService;
    }


    // POST /api/files
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        $data['user_id'] = auth()->id();

        $file = $this->fileService->uploadFileToGroup($data);
        if ($file) {
            return response()->json(['status' => true, 'message' => 'File uploaded successfully', 'data' => [$file ,'uploaded']], 200);

        } else {
            return response()->json(['status' => false, 'message' => 'File upload failed'], 500);
        }
    }



    // PUT/PATCH /api/files/{id}
    public function update(Request $request, $id): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            // Add other validation rules as needed
        ]);

        $updatedFile = $this->fileService->update($validatedData, $id);
        return response()->json($updatedFile);
    }

    // PATCH /api/files/{id}/set-active
    public function setActive(Request $request, $id): JsonResponse
    {
        $validatedData = $request->validate([
            'active' => 'required|boolean'
        ]);

        $updatedFile = $this->fileService->setActive($validatedData['active'], $id);
        return response()->json($updatedFile);
    }

    // PATCH /api/files/{id}/set-reserved
    public function setReserved(Request $request, $id): JsonResponse
    {
        $validatedData = $request->validate([
            'reserved' => 'required|boolean'
        ]);

        $updatedFile = $this->fileService->setReserved($validatedData['reserved'], $id);
        return response()->json($updatedFile);
    }

    // POST /api/files/reserve
    public function reserveFiles(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'file_ids' => 'required|array|min:1',
        ]);

        $result = $this->fileService->reserveFiles($validatedData['file_ids']);
        return response()->json(['success' => $result]);
    }


    public function uploadFileToGroup(Request $request): ?File
    {
        $validatedData = $request->validate([
            'file' => ['required', 'max:2048'],
            'group_id' => ['required', 'exists:groups,id'],
        ]);

        return $this->fileService->uploadFileToGroup($validatedData);
    }

    public function downloadFile(Request $request): \Illuminate\Http\Response|JsonResponse|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
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

            if (!$responseData) {
                throw new \Exception('Failed to process file or add event');
            }

            DB::commit();
            return response($responseData['content'], 200, $responseData['headers']);
        } catch (\Exception $e) {
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
        $data = $request->all();

        $validation = $request->validate([
            'file_id' => 'required|integer'
        ]);
        //dd("success");
        if (!$validation) {
            return response()->json(['status' => false, 'message' => "validation error"], 400);
        }

        $user_id = Auth::id();

        $data['user_id']=$user_id;
        $responseData=$this->fileService->deleteFile($data);
        DB::beginTransaction();
        try {

            if ($responseData)
            {
               $this->fileService->addFileEvent($data['file_id'],$user_id,3);
                    $file_id=$data['file_id'];
                    File::find($file_id)->delete();
                    DB::commit();
                    return response()->json(['status'=>true,'message'=>'File Deleted Successfully'],200);

            }
            else
            {
                return response()->json(['status'=>false,'message'=>'File not Deleted'],500);
            }
        }catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }

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
                $file_user_reserved->user_id = $file->user_id;
                $file_user_reserved->save();
            });

            return response()->json(['status' => true, 'message' => 'The File Has Been Reserved'], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => false, 'message' => $e->validator->errors()->first()], 400);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => 'An error occurred while processing your request'], 500);
        }
    }


}
