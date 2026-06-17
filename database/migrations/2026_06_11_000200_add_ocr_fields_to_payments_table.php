<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('slip_review_status')->default('needs_review')->after('slip_path');
            $table->unsignedTinyInteger('slip_ocr_score')->default(0)->after('slip_review_status');
            $table->longText('slip_ocr_text')->nullable()->after('slip_ocr_score');
            $table->string('slip_ocr_note')->nullable()->after('slip_ocr_text');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['slip_review_status','slip_ocr_score','slip_ocr_text','slip_ocr_note']);
        });
    }
};
