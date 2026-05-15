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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('offerte_nummer')->unique();
            $table->string('titel');
            $table->enum('status', ['concept', 'verzonden', 'gewonnen', 'verloren'])->default('concept');
            $table->date('geldig_tot')->nullable();
            $table->decimal('totaal', 10, 2)->default(0);
            $table->unsignedSmallInteger('versie')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
