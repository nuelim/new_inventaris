<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['routine', 'corrective', 'preventive', 'emergency'])->default('routine');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'semi_annual', 'annual', 'custom'])->default('monthly');
            $table->integer('custom_frequency_days')->nullable();
            $table->date('scheduled_date');
            $table->date('due_date');
            $table->date('completed_date')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'overdue', 'cancelled'])->default('scheduled');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->date('next_maintenance_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_id', 'status', 'due_date']);
            $table->index(['assigned_to', 'status']);
            $table->index(['scheduled_date', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};