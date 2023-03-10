<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Assets\Models\GenericDocument;

class CreateMicrosoftDocumentsTableAssets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(GenericDocument::TABLE_NAME, function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->integer('thumbnail_id')->unsigned();
            $table->foreign('thumbnail_id')->references('id')->on('files');
            $table->integer('document_id')->unsigned();
            $table->foreign('document_id')->references('id')->on('files');
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
        Schema::dropIfExists(GenericDocument::TABLE_NAME);
    }
}
