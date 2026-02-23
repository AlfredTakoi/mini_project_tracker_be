<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            // task that has dependencies
            $table->foreignId('task_id')
                  ->constrained('tasks')
                  ->onDelete('cascade');

            // the task it depends on
            $table->foreignId('dependency_id')
                  ->constrained('tasks')
                  ->onDelete('cascade');

            $table->unique(['task_id', 'dependency_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_dependencies');
    }
};