<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaikoUserStats extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taiko_user_stats', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->unique('user_id', 'osu_user_stats_user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('count300')->length(11)->default(0);
            $table->integer('count100')->length(11)->default(0);
            $table->integer('count50')->length(11)->default(0);
            $table->integer('countMiss')->length(11)->default(0);
            $table->bigInteger('ranked_score')->length(20)->default(0);
            $table->bigInteger('total_score')->length(20)->default(0);
            $table->mediumInteger('x_rank_count')->length(9)->default(0);
            $table->mediumInteger('s_rank_count')->length(9)->default(0);
            $table->mediumInteger('a_rank_count')->length(9)->default(0);
            $table->float('level')->unsigned();
            $table->integer('pp')->length(6)->default(0);
            $table->mediumInteger('playcount')->length(9)->default(0);
            $table->smallInteger('max_combo')->length(10)->default(0);
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
        Schema::drop('taiko_user_stats');
    }
}
