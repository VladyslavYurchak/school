<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('public_photo')->nullable()->after('note');
            $table->text('public_bio')->nullable()->after('public_photo');
            $table->boolean('is_public')->default(false)->after('public_bio');
            $table->unsignedInteger('public_sort_order')->default(0)->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'public_photo',
                'public_bio',
                'is_public',
                'public_sort_order'
            ]);
        });
    }
};
