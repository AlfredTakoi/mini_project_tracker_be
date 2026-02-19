<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use App\Interfaces\TaskRepositoryInterface;
use App\Http\Resources\TaskResource;

class TaskController extends Controller
{
    
    private TaskRepositoryInterface $taskRepository;
    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tasks = $this->taskRepository->getAll(
                $request->get('search'),
                $request->get('limit'),
                true
            );
            return ResponseHelper::jsonResponse(true, 'Data Task berhasil diambil', TaskResource::collection($tasks), 200);
        } catch (\Throwable $th) {
            return ResponseHelper::jsonResponse(false, 'Data Task gagal diambil', null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'status' => 'required',
            'weight' => 'required|integer',
            'project_id' => 'required|exists:projects,id',
        ]);
        try {
            $task = $this->taskRepository->create($validatedData);
            return ResponseHelper::jsonResponse(true, 'Task berhasil dibuat', $task, 201); // 201 Created status
        } catch (\Throwable $th) {
            return ResponseHelper::jsonResponse(false, 'Task gagal dibuat', null, 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id): JsonResponse
    {
        try {
            $task = $this->taskRepository->getById($id);
            if (!$task) {
                return ResponseHelper::jsonResponse(false, 'Task tidak ditemukan', null, 404);
            }
            return ResponseHelper::jsonResponse(true, 'Task berhasil diambil', new TaskResource($task), 200);
        } catch (\Throwable $th) {
            return ResponseHelper::jsonResponse(false, 'Task gagal diambil', null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'status' => 'required',
            'weight' => 'required|integer',
        ]);
        try {
            $task = $this->taskRepository->update($id, $validatedData);
            return ResponseHelper::jsonResponse(true, 'Task berhasil diupdate', $task, 200);
        } catch (\Throwable $th) {
            return ResponseHelper::jsonResponse(false, 'Task gagal diupdate', null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id): JsonResponse
    {
        try {
            $deleted = $this->taskRepository->delete($id);
            if (!$deleted) {
                return ResponseHelper::jsonResponse(false, 'Task tidak ditemukan', null, 404);
            }
            return ResponseHelper::jsonResponse(true, 'Task berhasil dihapus', null, 200);
        } catch (\Throwable $th) {
            return ResponseHelper::jsonResponse(false, 'Task gagal dihapus', null, 500);
        }
    }
}
