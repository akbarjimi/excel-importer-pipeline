<?php

use Akbarjimi\ExcelImporter\Enums\ExcelSheetStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excel_sheets', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('excel_file_id')->constrained()->onDelete('cascade');

            $table->string('name');

            $table->unsignedTinyInteger('sheet_index');

            $table->unsignedInteger('total_rows');

            $table->string('status', 32)->default(ExcelSheetStatus::PENDING)->index();

            $table->json('meta')->nullable();

            // Enforced at the database level so that upsert operations during
            // discovery retries never produce duplicate sheet rows.
            $table->unique(['excel_file_id', 'sheet_index']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excel_sheets');
    }
};
