<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excel_row_errors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('excel_row_id')
                ->constrained()
                ->onDelete('cascade')
                ->index();

            $table->string('field')->nullable();
            $table->string('error_type')->default('validation');
            $table->string('error_code')->nullable();
            $table->text('message');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excel_row_errors');
    }
};
