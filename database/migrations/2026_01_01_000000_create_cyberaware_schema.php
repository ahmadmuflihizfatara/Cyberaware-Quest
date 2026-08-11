<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Skema basis data proyek dipegang sebagai satu berkas DDL (deliverable mata
 * kuliah Basis Data). Migrasi ini hanya menjalankannya apa adanya supaya tidak
 * ada dua sumber kebenaran yang bisa saling menyimpang.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/schema.sql')));

        // schema.sql menutup dengan search_path = cyberaware saja; kembalikan ke
        // nilai konfigurasi agar tabel `migrations` di skema public tetap terlihat.
        DB::statement('SET search_path TO '.config('database.connections.pgsql.search_path'));
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS cyberaware CASCADE');
    }
};
