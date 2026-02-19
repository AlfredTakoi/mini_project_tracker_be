<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectStoreRequest;
use App\Interfaces\ProjectRepositoryInterface;
use Illuminate\Http\Request;
use App\Http\Resources\ProjectResource;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    private ProjectRepositoryInterface $projectRepository;
    public function __construct(ProjectRepositoryInterface $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $projects = $this->projectRepository->getAll(
                $request->get('search'),
                $request->get('limit'),
                true
            );
            return ResponseHelper::jsonResponse(true, 'Data Project berhasil diambil', ProjectResource::collection($projects), 200);
        } catch (\Throwable $th) {
            return ResponseHelper::jsonResponse(false, 'Data Project gagal diambil', null, 500);
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
        ]);
        try {
            $project = $this->projectRepository->create($validatedData); // Create
            return ResponseHelper::jsonResponse(true, 'Project berhasil dibuat', $project, 201); // 201 Created status
        } catch (\Throwable $th) {
            return ResponseHelper::jsonResponse(false, 'Project gagal dibuat', null, 500);
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
            $project = $this->projectRepository->getById($id);
            if (!$project) {
                return ResponseHelper::jsonResponse(false, 'Project tidak ditemukan', null, 404);
            }
            return ResponseHelper::jsonResponse(true, 'Project berhasil diambil', new ProjectResource($project), 200);
        } catch (\Throwable $th) {
            return ResponseHelper::jsonResponse(false, 'Project gagal diambil', null, 500);
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
        ]);
        try {
            $project = $this->projectRepository->update($id, $validatedData); // Update
            return ResponseHelper::jsonResponse(true, 'Project berhasil diupdate', $project, 200);
        } catch (\Throwable $th) {
            return ResponseHelper::jsonResponse(false, 'Project gagal diupdate', null, 500);
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
            $this->projectRepository->delete($id); // Delete
            return ResponseHelper::jsonResponse(true, 'Project berhasil dihapus', null, 200);
        } catch (\Throwable $th) {
            return ResponseHelper::jsonResponse(false, 'Project gagal dihapus', null, 500);
        }
    }
}
