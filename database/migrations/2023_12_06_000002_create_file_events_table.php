<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            // First, create the file_events table
            Schema::create('file_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('file_id');
                $table->unsignedBigInteger('event_type_id');
                $table->unsignedBigInteger('user_id');
                $table->date('date');
                $table->text('details')->nullable();
                $table->timestamps();

                // Add foreign key constraints after table creation
                $table->foreign('file_id')
                    ->references('id')
                    ->on('files')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreign('event_type_id')
                    ->references('id')
                    ->on('event_types')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });

            // Now, let's insert some data into the event_types table
            DB::table('event_types')->insert([
                ['name' => 'Download', 'description' => null],
                ['name' => 'Upload', 'description' => null],
                ['name' => 'delete', 'description' => null],
                ['name' => 'reserve', 'description' => null],
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            DB::table('event_types')->delete();
            Schema::dropIfExists('file_events');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
