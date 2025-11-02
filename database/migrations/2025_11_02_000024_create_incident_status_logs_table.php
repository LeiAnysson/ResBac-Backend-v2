<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidentStatusLogsTable extends Migration
{
    public function up()
    {
        Schema::create('incident_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incident_reports')->onDelete('cascade');
            $table->foreignId('updated_by')->constrained('users')->onDelete('cascade');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('incident_status_logs');
    }
}

