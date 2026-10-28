<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('statut', ['en_attente', 'en_cours', 'livree', 'annulee'])->default('en_attente')->change();
        });
    }

    public function down(): void
    {
        DB::table('courses')->where('statut', 'annulee')->update(['statut' => 'en_attente']);

        Schema::table('courses', function (Blueprint $table) {
            $table->enum('statut', ['en_attente', 'en_cours', 'livree'])->default('en_attente')->change();
        });
    }
};
