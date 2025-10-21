<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('category')->comment('IT, Kendaraan, Bangunan, Mesin, Lainnya');
            $table->integer('depreciation_years')->default(5);
            $table->boolean('requires_calibration')->default(false);
            $table->boolean('requires_maintenance')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'is_active']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_types');
    }
};