<?php

namespace App\Http\Controllers;

use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class FileController extends Controller
{
    private FileService $fileService;

    public function __construct( FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    // GET /api/files
    public function index(): JsonResponse
    {
        $files = $this->fileService->all();
        return response()->json($files);
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


    // GET /api/files/{id}
    public function show($id): JsonResponse
    {
        $file = $this->fileService->findById($id);
        return response()->json($file);
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

    // DELETE /api/files/{id}
    public function destroy($id): JsonResponse
    {
        $this->fileService->delete($id);
        return response()->json(['message' => 'File deleted successfully'], 200);
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

}
