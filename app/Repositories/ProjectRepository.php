<?php

namespace App\Repositories;
use App\Interfaces\ProjectRepositoryInterface;
use App\Models\Project;
use App\Repositories\TaskRepository;
use Illuminate\Support\Facades\DB;

class ProjectRepository implements ProjectRepositoryInterface {

    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute
    ) {
        $query = Project::where(function ($query) use ($search, $limit, $execute) {
            if ($search) {
                $query->search($search);
            }
        });

        $query->orderBy('created_at', 'desc');

         // if total task is 0, then project is draft
        $query->withCount(['tasks as total_tasks_count']);

        if ($limit) {
            $query->take($limit);
        }

        if ($execute) {
            $projects = $query->get();

            // ensure project stats are recalculated before returning
            $taskRepo = new TaskRepository();
            foreach ($projects as $project) {
                $taskRepo->recalcProject($project->id);
            }

            return $projects;
        }

        return $query;
    }
    public function getById(string $id)
    {
        $query = Project::where('id', $id);
        return $query->first();
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $project = new Project();
            $project->name = $data['name'];
            $project->status = 'draft';
            $project->completion_progress = 0;

            $project->save();

            DB::commit();
            return $project;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function update(string $id, array $data)
    {
        DB::beginTransaction();
        try {
            $project = Project::find($id);
            $project->name = $data['name'];
            if (isset($data['status'])) {
                $project->status = $data['status'];
            }
            $project->completion_progress = 0;
            $project->save();

            DB::commit();
            return $project;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function delete(string $id)
    {
        DB::beginTransaction();
        try {
            $project = Project::find($id);
            if ($project) {
                $project->delete();
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
}
