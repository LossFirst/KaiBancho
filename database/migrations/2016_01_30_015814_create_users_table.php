<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->string('password', 255);
            $table->rememberToken();
            $table->integer('usergroup')->length(2);
            $table->integer('country')->unsigned();
            $table->foreign('country')->references('id')->on('osu_country')->onDelete('cascade');
            $table->text('avatar');
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
        Schema::drop('users');
    }
}
