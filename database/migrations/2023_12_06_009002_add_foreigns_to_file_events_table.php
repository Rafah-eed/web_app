<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('file_events', function (Blueprint $table) {
            $table
                ->foreign('file_id')
                ->references('id')
                ->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('event_type_id')
                ->references('id')
                ->on('event_types')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_events', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
            $table->dropForeign(['event_type_id']);
            $table->dropForeign(['user_id']);
        });
    }
};
