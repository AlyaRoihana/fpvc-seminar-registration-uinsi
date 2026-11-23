<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seminars', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('speaker')->nullable();
            $table->dateTime('seminar_date');
            $table->string('location');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('registered_count')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Laravel 11 belum menyediakan $table->check(), jadi constraint
        // ditambahkan lewat DDL eksplisit agar berlaku di MySQL/PostgreSQL.
        DB::statement('ALTER TABLE seminars ADD CONSTRAINT seminars_capacity_check CHECK (capacity >= 0)');
        DB::statement('ALTER TABLE seminars ADD CONSTRAINT seminars_registered_count_check CHECK (registered_count <= capacity)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seminars');
    }
};
