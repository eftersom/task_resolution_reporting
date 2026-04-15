<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->cascadeOnDelete();
            $table->foreignId('resolution_type_id')->nullable()->constrained('resolution_types')->nullOnDelete();
            $table->dateTime('work_started_at')->nullable();
            $table->dateTime('work_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_tasks');
    }
};
