<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCalbumCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calbum_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category_name',64)->unique();
            //$table->integer('user_id')->unsigned()->index();
            //$table->foreign('user_id')->on('users')->references('id');
            $table->softDeletes(); //PER CANCELLARE LE CATEGORIE LOGICAMENTE NON FISICAMENTE 
            $table->timestamps();
        });

        //TABELLA ASSOCIATIVA
        Schema::create('album_category', function (Blueprint $table) { //é LA MIA TABELLA DI MEZZO
            $table->increments('id');
            $table->integer('album_id')->unsigned(); //chiave esterna riferita alla tabella albums
            $table->integer('category_id')->unsigned(); //chiave estrena riferita alla tabella calbum_categories 
            $table->unique(['album_id','category_id']);// devono essere unici ,non possono essere dublicati ( 1-1 /2-2)
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
        Schema::dropIfExists('calbum_categories');
        Schema::dropIfExists('album_category');

    }
}
