<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesTableAssets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->integer('small_id')->unsigned();
//            $table->foreign('small_id')->references('id')->on('files');
            $table->integer('medium_id')->unsigned();
//            $table->foreign('medium_id')->references('id')->on('files');
            $table->integer('image_id')->unsigned();
//            $table->foreign('image_id')->references('id')->on('files');
            $table->integer('large_id')->unsigned();
//            $table->foreign('large_id')->references('id')->on('files');
            $table->softDeletes();
            $table->timestamps();
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
