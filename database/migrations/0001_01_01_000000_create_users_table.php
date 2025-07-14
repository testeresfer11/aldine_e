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
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('first_name')->nullable(); 
            $table->string('last_name')->nullable();
            $table->string('email')->unique(); 
            $table->timestamp('email_verified_at')->nullable(); 
            $table->tinyInteger('is_email_verified')->default(0);
            $table->string('password'); 
            $table->date('birthday'); 
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('profile_pic')->nullable(); 
            $table->string('country')->nullable();
            $table->string('country_code')->nullable();
            $table->string('country_short_code')->nullable();
            $table->string('address')->nullable();
            $table->text('phone_number')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_profile_updated')->default(0);
            $table->tinyInteger('notification_enabled')->default(0);

            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
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
