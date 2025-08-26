<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('incident_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('priority_name')->unique();
            $table->unsignedInteger('priority_level')->unique(); 
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('incident_priorities');
    }
};
