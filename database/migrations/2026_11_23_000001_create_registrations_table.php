<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars');
            $table->foreignId('user_id')->constrained('users');
            $table->uuid('ticket_code')->unique();
            $table->dateTime('checked_in_at')->nullable();
            $table->timestamps();

            // BR-02: satu mahasiswa hanya satu e-ticket per seminar.
            $table->unique(['seminar_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
