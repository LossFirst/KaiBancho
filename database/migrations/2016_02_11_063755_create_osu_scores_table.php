<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOsuScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('osu_scores', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('beatmapHash', 32);
            $table->foreign('beatmapHash')->references('checksum')->on('osu_beatmaps');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('score');
            $table->enum('rank', ['0','A','B','C','D','S','SH','X','XH','F'])->default('F');
            $table->integer('combo')->length(9);
            $table->integer('count50')->length(9);
            $table->integer('count100')->length(9);
            $table->integer('count300')->length(9);
            $table->integer('countMiss')->length(9);
            $table->integer('countKatu')->length(9);
            $table->integer('countGeki')->length(9);
            $table->boolean('fc');
            $table->string('mods',10);
            $table->boolean('pass');
            $table->integer('checksum')->length(16); //I think I got this?
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
        Schema::drop('osu_scores');
    }
}
