<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username', 100)->unique()->nullable(); // optional
            $table->string('email', 100)->unique();
            $table->string('password', 100)->nullable()->comment('minimum 6 character');
            $table->string('phone', 14)->unique()->nullable();
            $table->string('avatar')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('zip')->nullable();
            $table->string('timezone')->nullable();
            $table->enum('gender', ['male', 'female', 'none'])->default('male');
            $table->enum('online', ['online', 'offline', 'away'])->default('offline');
            $table->enum('status', ['pending', 'active', 'suspend'])->default('pending');
            $table->string('email_verification_token', 100)->nullable();
            $table->boolean('is_email_verified')->default(false);
            $table->string('phone_verification_otp', 6)->nullable();
            $table->boolean('is_phone_verified')->default(false);
            $table->string('password_reset_token', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('last_password_reset_at')->nullable();
            $table->timestamp('last_sign_in_at')->nullable();
            $table->softDeletes();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
