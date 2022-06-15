<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExerciseSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exercise_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('file_path')->nullable();
            $table->integer('grade')->max(100)->nullable();
            $table->text('content');
            $table->enum('status', ['assigned', 'finished', 'overdue', 'closed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exercise_submissions');
    }
}
