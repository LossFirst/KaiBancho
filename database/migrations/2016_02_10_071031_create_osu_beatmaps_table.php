<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOsuBeatmapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('osu_beatmaps', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->mediumInteger('beatmapset_id');
            $table->mediumInteger('beatmap_id');
            $table->string('checksum');
            $table->string('version');
            $table->string('title');
            $table->string('creator');
            $table->smallInteger('bpm');
            $table->mediumInteger('total_length');
            $table->mediumInteger('hit_length');
            $table->smallInteger('countTotal');
            $table->float('diff_drain');
            $table->float('diff_size');
            $table->float('diff_overall');
            $table->float('diff_approach');
            $table->tinyInteger('playmode');
            $table->tinyInteger('approved');
            $table->float('difficultyrating');
            $table->mediumInteger('playcount');
            $table->mediumInteger('passcount');
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
        Schema::drop('osu_beatmaps');
    }
}
