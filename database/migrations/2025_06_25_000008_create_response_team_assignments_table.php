<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('response_team_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incident_id');
            $table->unsignedBigInteger('dispatcher_id');
            $table->unsignedBigInteger('team_id');
            $table->string('status');
            $table->timestamp('assigned_at');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('incident_id')->references('id')->on('incident_reports')->onDelete('cascade');
            $table->foreign('dispatcher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('response_teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('response_team_assignments');
    }
};
