<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sensor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('greenhouse_id', 8);
            $table->string('node_id', 8);
            $table->string('parameter', 32);
            $table->double('value');
            $table->timestamps();

            $table->index(
                ['greenhouse_id', 'node_id', 'parameter', 'created_at'],
                'sensor_logs_gh_node_param_created_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_logs');
    }
};
