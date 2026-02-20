<?php

namespace App\Repositories;
use App\Interfaces\TaskRepositoryInterface;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class TaskRepository implements TaskRepositoryInterface {

    public function recalcProject(int $projectId): void
    {
        $project = Project::find($projectId);
        if (!$project) {
            return;
        }

        $total_task        = Task::where('project_id', $projectId)->count();
        $task_done         = Task::where('project_id', $projectId)->where('status', 'done')->count();
        $task_draft        = Task::where('project_id', $projectId)->where('status', 'draft')->count();
        $total_weight_done = Task::where('project_id', $projectId)->where('status', 'done')->sum('weight');
        $total_weight_task = Task::where('project_id', $projectId)->sum('weight');

        if ($total_task === 0) {
            $project->status = 'draft';
            $project->completion_progress = 0;
        } elseif ($task_done === $total_task) {
            // all done
            $project->status = 'done';
            $project->completion_progress = 100;
        } elseif ($task_draft > 0) {
            // at least 1 draft, so project is still draft
            $project->status = 'draft';
            $project->completion_progress = ($total_weight_done / $total_weight_task) * 100;
        } else {
            // no draft, but not all done (in_progress)
            $project->status = 'in_progress';
            $project->completion_progress = ($total_weight_done / $total_weight_task) * 100;
        }

        $project->save();
    }

    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute
    ) {
        $query = Task::where(function ($query) use ($search, $limit, $execute) {
            if ($search) {
                $query->search($search);
            }
        });

        $query->orderBy('created_at', 'desc');

        if ($limit) {
            $query->take($limit);
        }

        if ($execute) {
            return $query->get();
        }

        return $query;
    }
    public function getById(string $id)
    {
        $query = Task::where('id', $id);
        return $query->first();
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $task = new Task();
            
            $task->name = $data['name'];
            $task->status = $data['status'];
            $task->weight = $data['weight'];
            $task->project_id = $data['project_id'];
            
            $task->save();
            $projectId = $task->project_id;
            
            $this->recalcProject($projectId);

            DB::commit();
            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function update(string $id, array $data)
    {
        DB::beginTransaction();
        try {
            $task = Task::find($id);
            $project = Project::find($task->project_id);
            $oldProject = $task->project_id;

            $task->name = $data['name'];
            $task->status = $data['status'];
            $task->weight = $data['weight'];
            if (isset($data['project_id'])) {
                $task->project_id = $data['project_id'];
            }
            $task->save();

            $this->recalcProject($oldProject);

            DB::commit();
            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function delete(string $id)
    {
        DB::beginTransaction();
        try {
            $task = Task::find($id);
            $projectId = $task->project_id;
            if ($task) {
                $task->delete();
                $this->recalcProject($projectId);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
}
