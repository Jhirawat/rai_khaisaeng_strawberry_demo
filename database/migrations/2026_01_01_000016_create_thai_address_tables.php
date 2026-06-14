<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('thai_provinces', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->index();
            $table->string('name_th')->index();
            $table->string('name_en')->nullable();
            $table->timestamps();
        });
        Schema::create('thai_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('thai_provinces')->cascadeOnDelete();
            $table->string('code')->nullable()->index();
            $table->string('name_th')->index();
            $table->string('name_en')->nullable();
            $table->timestamps();
        });
        Schema::create('thai_subdistricts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('thai_districts')->cascadeOnDelete();
            $table->string('code')->nullable()->index();
            $table->string('name_th')->index();
            $table->string('name_en')->nullable();
            $table->string('zip_code', 10)->nullable()->index();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('thai_subdistricts');
        Schema::dropIfExists('thai_districts');
        Schema::dropIfExists('thai_provinces');
    }
};
