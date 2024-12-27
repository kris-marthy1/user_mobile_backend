<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key
            $table->string('ip_address'); // to store the IP address
            $table->string('school_id'); // to store the IP address
            $table->unsignedBigInteger('window_id'); // to store the window_id as a foreign key
            $table->timestamps(); // created_at and updated_at columns

            // Add foreign key constraint for window_id
            $table->foreign('window_id')->references('window_id')->on('window')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_sessions');
    }
}
