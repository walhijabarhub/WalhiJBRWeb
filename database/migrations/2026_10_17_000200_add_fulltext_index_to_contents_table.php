<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FULLTEXT index for public search (MySQL/MariaDB only; SQLite keeps LIKE search).
     */
    public function up(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('contents', function (Blueprint $table) {
            $table->fullText(['title', 'body'], 'ft_contents_title_body');
        });
    }

    public function down(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('contents', function (Blueprint $table) {
            $table->dropFullText('ft_contents_title_body');
        });
    }
};
