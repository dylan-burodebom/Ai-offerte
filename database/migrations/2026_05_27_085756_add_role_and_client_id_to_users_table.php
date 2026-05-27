<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'medewerker', 'klant'])->default('medewerker')->after('email');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete()->after('role');
        });

        // Promote the configured admin email to admin role
        $adminEmail = config('app.admin_email');
        if ($adminEmail) {
            DB::table('users')->where('email', $adminEmail)->update(['role' => 'admin']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['role', 'client_id']);
        });
    }
};
