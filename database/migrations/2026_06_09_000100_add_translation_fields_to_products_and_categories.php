<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'name_en')) {
                $table->string('name_en')->nullable()->after('name');
            }
            if (!Schema::hasColumn('categories', 'description_en')) {
                $table->text('description_en')->nullable()->after('description');
            }
        });
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'name_en')) {
                $table->string('name_en')->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'description_en')) {
                $table->text('description_en')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'description_en')) $table->dropColumn('description_en');
            if (Schema::hasColumn('products', 'name_en')) $table->dropColumn('name_en');
        });
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'description_en')) $table->dropColumn('description_en');
            if (Schema::hasColumn('categories', 'name_en')) $table->dropColumn('name_en');
        });
    }
};
