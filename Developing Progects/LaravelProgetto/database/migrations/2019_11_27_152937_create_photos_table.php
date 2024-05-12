<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',128);
            $table->text('description');
            $table->integer('album_id')->unsigned(); //non negativo 
            $table->string('filename')->nullable();
            $table->string('mime')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('img_path',128);
            $table->foreign('album_id')->on('albums')->references('id')->onDelete('cascade')->onUpdate('cascade');
            $table->softDeletes();  
            $table->timestamps();  /*è OBBLIGATORIO METTERLA mi crea nel database due colonne created_at e update_at*/
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photos');
    }
}
