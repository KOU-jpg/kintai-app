<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->nullable();
            $table->date('work_date');
            $table->dateTime('shift_start')->nullable();
            $table->dateTime('shift_end')->nullable();
            $table->integer('total_work_minutes')->default(0);
             $table->json('break_time')->nullable();
            $table->integer('break_minutes')->default(0);
            $table->integer('duration_minutes')->default(0);     
            $table->string('note', 255)->nullable();      
            $table->enum('request_status', ['pending', 'approved'])->nullable();
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('attendance_requests');
    }
}
