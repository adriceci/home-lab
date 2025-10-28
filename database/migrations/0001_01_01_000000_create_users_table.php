<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Unique identifier for the user');
            $table->string('name')->comment('Full name of the user');
            $table->string('email')->unique()->comment('User email address, must be unique');
            $table->timestamp('email_verified_at')->nullable()->comment('Timestamp when the email was verified');
            $table->string('password')->comment('Hashed password for user authentication');
            $table->boolean('is_admin')->default(false)->comment('Whether the user is an administrator');
            $table->rememberToken()->comment('Token for "remember me" functionality');
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('Email address for password reset');
            $table->string('token')->comment('Reset token for password recovery');
            $table->timestamp('created_at')->nullable()->comment('When the reset token was created');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Unique session identifier');
            $table->foreignUuid('user_id')->nullable()->index()->comment('ID of the user associated with this session');
            $table->string('ip_address', 45)->nullable()->comment('IP address from which the session was created');
            $table->text('user_agent')->nullable()->comment('User agent string from the browser');
            $table->longText('payload')->comment('Serialized session data');
            $table->integer('last_activity')->index()->comment('Unix timestamp of last activity in this session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
