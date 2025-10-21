<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('asset_type_id')->constrained()->onDelete('restrict');
            $table->foreignId('location_id')->constrained()->onDelete('restrict');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->unique()->nullable();
            $table->string('part_number')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor'])->default('good');
            $table->enum('status', ['active', 'inactive', 'maintenance', 'calibration', 'retired', 'lost', 'damaged'])->default('active');
            $table->decimal('current_value', 15, 2)->nullable();
            $table->string('qr_code')->unique()->nullable();
            $table->string('barcode')->unique()->nullable();
            $table->json('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sku', 'status']);
            $table->index(['asset_type_id', 'location_id']);
            $table->index(['status', 'warranty_expiry']);
            $table->index(['qr_code', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};