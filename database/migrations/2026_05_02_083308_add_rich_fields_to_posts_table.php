<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('category')->default('knowledge');
            $table->string('link')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('admin_evaluation')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['category', 'link', 'image_path', 'is_anonymous', 'admin_evaluation']);
        });
    }
};
