<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v2_plugins', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique()->comment('Plugin code');
            $table->string('name', 255)->comment('Plugin name');
            $table->string('version', 50)->comment('Plugin version');
            $table->string('type', 50)->default('feature')->comment('Plugin type: feature, payment');
            $table->boolean('is_enabled')->default(false)->comment('Is plugin enabled');
            $table->json('config')->nullable()->comment('Plugin configuration');
            $table->timestamp('installed_at')->nullable()->comment('Installation time');
            $table->timestamps();
            
            $table->index('type');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v2_plugins');
    }
};
