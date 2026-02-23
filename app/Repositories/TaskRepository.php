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
        $query = Task::with('dependencies')->where(function ($query) use ($search, $limit, $execute) {
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
        return Task::with('dependencies')->find($id);
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

            // handle dependencies after initial save so $task->id exists
            if (!empty($data['dependencies'])) {
                $this->validateAndSyncDependencies($task, $data['dependencies']);
            }

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
            if (!$task) {
                throw new \Exception('Task not found');
            }

            $oldStatus = $task->status;
            $oldProject = $task->project_id;

            $task->name = $data['name'];
            $task->status = $data['status'];
            $task->weight = $data['weight'];
            if (isset($data['project_id'])) {
                $task->project_id = $data['project_id'];
            }

            // if the task is being marked done, ensure dependencies are done.
            if ($task->status === 'done' && !$task->canBeMarkedDone()) {
                throw new \Exception('Cannot mark task done while one or more dependencies are not done');
            }

            $task->save();

            // sync dependencies if provided
            if (array_key_exists('dependencies', $data)) {
                $this->validateAndSyncDependencies($task, $data['dependencies']);
            }

            // if status has been moved away from done, we need to revalidate dependents
            if ($oldStatus === 'done' && $task->status !== 'done') {
                $this->revalidateDependents($task);
            }

            $this->recalcProject($oldProject);

            DB::commit();
            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Sync dependencies for an existing task by id.
     */
    public function syncDependencies(string $id, array $dependencyIds)
    {
        $task = Task::findOrFail($id);
        $this->validateAndSyncDependencies($task, $dependencyIds);
    }

    /**
     * Validate a list of dependencies and attach them to the given task.
     *
     * @param Task $task
     * @param array $dependencyIds
     * @throws \Exception when validation fails (circular, cross-project, self)
     */
    private function validateAndSyncDependencies(Task $task, array $dependencyIds): void
    {
        // remove duplicates and self reference immediately
        $dependencyIds = array_unique($dependencyIds);
        if (in_array($task->id, $dependencyIds, true)) {
            throw new \Exception('A task cannot depend on itself');
        }

        // load tasks and ensure they exist and belong to same project
        $deps = Task::whereIn('id', $dependencyIds)->get();
        if (count($deps) !== count($dependencyIds)) {
            throw new \Exception('One or more dependencies do not exist');
        }
        foreach ($deps as $dep) {
            if ($dep->project_id !== $task->project_id) {
                throw new \Exception('Dependencies must belong to the same project');
            }
        }

        // detect circular reference
        if ($task->hasCircularDependency($dependencyIds)) {
            throw new \Exception('Circular dependency detected');
        }

        $task->dependencies()->sync($dependencyIds);
    }

    /**
     * Walk dependents recursively and downgrade any tasks that are no longer
     * allowed to remain done because one of their own dependencies is not done.
     *
     * @param Task $task  the task whose status changed away from done
     */
    private function revalidateDependents(Task $task): void
    {
        $queue = [$task];

        while (!empty($queue)) {
            /** @var Task $current */
            $current = array_shift($queue);
            $dependents = $current->dependents()->where('status', 'done')->get();

            foreach ($dependents as $dependent) {
                if (!$dependent->canBeMarkedDone()) {
                    $dependent->status = 'in_progress';
                    $dependent->save();
                    $this->recalcProject($dependent->project_id);
                    $queue[] = $dependent;
                }
            }
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
