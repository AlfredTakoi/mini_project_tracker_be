<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'weight',
        'project_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Tasks that this task depends on.
     */
    public function dependencies()
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'dependency_id'
        );
    }

    /**
     * Tasks that depend on this task.
     */
    public function dependents()
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'dependency_id',
            'task_id'
        );
    }

    public function canBeMarkedDone(): bool
    {
        // qualify the status column and ignore ambiguous id
        return $this->dependencies()
                    ->where('tasks.status', '!=', 'done')
                    ->count() === 0;
    }

    public function hasCircularDependency($candidateIds): bool
    {
        $visited = [];
        $stack = is_array($candidateIds) ? $candidateIds : $candidateIds->pluck('id')->toArray();

        while (!empty($stack)) {
            $currentId = array_pop($stack);
            if ($currentId == $this->id) {
                return true;
            }
            if (in_array($currentId, $visited, true)) {
                continue;
            }
            $visited[] = $currentId;

            $currentTask = Task::find($currentId);
            if (!$currentTask) {
                continue;
            }

            // explicitly select task id to avoid pivot column collision
            $deps = $currentTask->dependencies()->pluck('tasks.id')->toArray();
            foreach ($deps as $d) {
                if (!in_array($d, $visited, true)) {
                    $stack[] = $d;
                }
            }
        }

        return false;
    }
}
