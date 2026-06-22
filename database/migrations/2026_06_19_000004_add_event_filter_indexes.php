<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->index('created_time');
            $table->index(['latitude', 'longitude']);
            $table->index(['status', 'created_time']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex(['created_time']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['status', 'created_time']);
        });
    }
};
