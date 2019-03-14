<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->string('filename');
            $table->string('mime_type');
            $table->string('checksum');
            $table->bigInteger('size')->unsigned();
            $table->bigInteger('api_key_id')->nullable()->unsigned()->index();
            $table->foreign('api_key_id')->references('id')->on('api_keys')->onDelete('set null');
            $table->timestamp('last_viewed')->nullable();
            $table->timestamp('created')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('images');
    }
}
