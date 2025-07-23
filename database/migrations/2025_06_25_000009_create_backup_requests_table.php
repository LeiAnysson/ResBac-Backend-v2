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
        Schema::create('backup_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('responder_id');
            $table->unsignedBigInteger('incident_id');
            $table->string('status')->default('Pending');
            $table->timestamp('requested_at');
            $table->string('backup_type');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('responder_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('incident_id')->references('id')->on('incident_reports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_requests');
    }
};
