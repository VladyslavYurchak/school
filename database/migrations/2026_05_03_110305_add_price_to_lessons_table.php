<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('position');
            }

            if (!Schema::hasColumn('lessons', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'is_published')) {
                $table->dropColumn('is_published');
            }

            if (Schema::hasColumn('lessons', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
