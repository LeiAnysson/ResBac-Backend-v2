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
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('incident_type_id');
            $table->string('caller_name')->nullable();
            $table->decimal('latitude',10,7)->nullable();
            $table->decimal('longitude',10,7)->nullable();
            $table->text('landmark')->nullable();
            $table->string('status');
            $table->timestamp('reported_at')->useCurrent();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('reported_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('incident_type_id')->references('id')->on('incident_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};
