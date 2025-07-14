<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('work_date');
            $table->dateTime('shift_start')->nullable();
            $table->dateTime('shift_end')->nullable();
            $table->integer('break_minutes')->default(0);
            $table->enum('status', ['before_work', 'working', 'on_break', 'after_work'])->default('before_work');
            $table->string('note', 255)->nullable(); 
            $table->integer('total_work_minutes')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
