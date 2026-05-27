<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Bank
            $table->string('bank')->nullable()->after('beschrijving');
            $table->string('bic')->nullable()->after('bank');
            $table->string('iban')->nullable()->after('bic');
            $table->string('rekeninghouder')->nullable()->after('iban');
            $table->string('vestigingsplaats')->nullable()->after('rekeninghouder');

            // Administratie
            $table->boolean('gebruik_afwijkende_factuurgegevens')->default(false)->after('vestigingsplaats');

            // Extra
            $table->string('kvk_nummer')->nullable()->after('gebruik_afwijkende_factuurgegevens');
            $table->string('rechtsvorm')->nullable()->after('kvk_nummer');
            $table->string('btw_nummer')->nullable()->after('rechtsvorm');
            $table->string('extern_id')->nullable()->after('btw_nummer');

            // Instellingen
            $table->foreignId('relatiebeheerder_id')->nullable()->constrained('users')->nullOnDelete()->after('extern_id');
            $table->string('voertaal')->nullable()->after('relatiebeheerder_id');
            $table->string('taal_berichten')->nullable()->after('voertaal');
            $table->json('labels')->nullable()->after('taal_berichten');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['relatiebeheerder_id']);
            $table->dropColumn([
                'bank', 'bic', 'iban', 'rekeninghouder', 'vestigingsplaats',
                'gebruik_afwijkende_factuurgegevens',
                'kvk_nummer', 'rechtsvorm', 'btw_nummer', 'extern_id',
                'relatiebeheerder_id', 'voertaal', 'taal_berichten', 'labels',
            ]);
        });
    }
};
