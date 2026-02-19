<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'completion_progress',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
